<?php

namespace Idimption\Entity;

use Chameleon\PhpDiff\StringDiffOperation;
use Idimption\Db;
use Idimption\Differ;
use Idimption\Entity\FieldHook\BaseFieldHook;
use Idimption\Exception\BadRequestException;
use Idimption\Exception\InternalServerErrorException;
use Idimption\Html;
use Idimption\Map;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseEntity implements JsonSerializable
{
    protected static $_instances = [];

    /**
     * @return static
     */
    public static function getInstance()
    {
        $class = static::class;
        return self::$_instances[$class] = self::$_instances[$class] ?? new $class();
    }

    public static function resetInstances()
    {
        self::$_instances = [];
    }

    /** @var string */
    private $_tableName;

    /** @var string */
    private $_viewName;

    /** @var BaseEntity[]|null */
    private $_allRowsCache = null;

    /** @var array[][] */
    private $_rowMapCache = [];

    /** @var GuidMap|null */
    private $_guidMap = null;

    /** @var RowChange[] */
    private $_changes = [];

    /**
     * @var int
     * @hook AutoIncrement
     * @readOnly
     */
    public $id;

    protected function __construct($data, $tableName, $viewName = null)
    {
        $this->_tableName = $tableName;
        $this->_viewName = $viewName ?? $tableName;
        $this->setFromArray($data, false);
    }

    public function allowAnonymousCreate()
    {
        return false;
    }

    public function getGuidMap()
    {
        return $this->_guidMap;
    }

    public function setGuidMap($guidMap)
    {
        $this->_guidMap = $guidMap;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @return string
     */
    public function getViewName()
    {
        return $this->_viewName;
    }

    /**
     * @return BaseEntity[]
     */
    public function getAllRows()
    {
        if ($this !== static::getInstance()) {
            throw new InternalServerErrorException();
        }
        return $this->_allRowsCache = $this->_allRowsCache ?? array_map([get_class($this), 'fromJson'], Db::getInstance()->selectAll($this->_viewName));
    }

    /**
     * @param string[] $fields
     * @param string|null $resultField
     * @param bool $multiple
     * @return array
     */
    public function getRowsMap($fields = ['id'], $resultField = null, $multiple = false)
    {
        if ($this !== static::getInstance()) {
            throw new InternalServerErrorException();
        }
        $key = json_encode(func_get_args());
        return $this->_rowMapCache[$key] = $this->_rowMapCache[$key] ?? Map::map($this->getAllRows(), $fields, $resultField, $multiple);
    }

    /**
     * @return static[]
     */
    public function getAllRowsById()
    {
        return $this->getRowsMap();
    }

    /**
     * @param int $id
     * @return static|null
     */
    public function getRowById($id)
    {
        return $this->getAllRowsById()[$id] ?? null;
    }

    public function clearCache()
    {
        if ($this !== static::getInstance()) {
            throw new InternalServerErrorException();
        }
        $this->_allRowsCache = null;
        $this->_rowMapCache = [];
    }

    /**
     * @return string[]
     */
    public function getAllFields()
    {
        $class = new ReflectionClass($this);
        return array_map(
            function(ReflectionProperty $property) {
                return $property->name;
            },
            $class->getProperties(ReflectionProperty::IS_PUBLIC)
        );
    }

    /**
     * @return string[]
     */
    public function getVisibleFields()
    {
        return array_values(array_filter(
            $this->getAllFields(),
            function($fieldName) {
                $fieldInfo = $this->getFieldInfo($fieldName);
                return empty($fieldInfo['hidden']);
            }
        ));
    }

    /**
     * @param array $info
     * @return BaseEntity|null
     */
    private function _getForeignModelByFieldInfo($info)
    {
        if (isset($info['foreignClass'])) {
            /** @var self|string $foreignKeyClassName */
            $foreignKeyClassName = $info['foreignClass'] === 'static'
                ? get_class($this)
                : __NAMESPACE__ . '\\' . $info['foreignClass'];
            return $foreignKeyClassName::getInstance();
        } else {
            return null;
        }
    }

    public function getFieldInfo($fieldName)
    {
        $info = [];
        $field = new ReflectionProperty($this, $fieldName);

        // As long as it's a singleton instance, the current field value should be the default one
        $info['default'] = $field->getValue($this);

        preg_match_all('~\@(\S+) ?([^\r\n]*)~', $field->getDocComment() ?: '', $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $info[$match[1]] = $match[2] === '' ? true : $match[2];
        }

        $types = explode('|', $info['var']);
        if (in_array('null', $types)) {
            $info['optional'] = true;
        }

        $foreignModel = $this->_getForeignModelByFieldInfo($info);
        if ($foreignModel) {
            $info['foreignTable'] = $foreignModel->getTableName();
        }

        if (!empty($info['format'])) {
            $info['format'] = json_decode('{' . $info['format'] . '}', true) ?: [];
        } else {
            $info['format'] = $info['var'] === 'bool' ? ['yes' => true, 'no' => false] : [];
        }

        return $info;
    }

    public function getForeignModel($fieldName)
    {
        return $this->_getForeignModelByFieldInfo($this->getFieldInfo($fieldName));
    }

    public function getFieldsInfo()
    {
        $result = [];
        foreach ($this->getVisibleFields() as $fieldName) {
            $result[$fieldName] = $this->getFieldInfo($fieldName);
        }
        return $result;
    }

    public function getSchema()
    {
        return [
            'tableName' => $this->_tableName,
            'className' => get_class($this),
            'fields' => $this->getVisibleFields(),
            'fieldsInfo' => $this->getFieldsInfo(),
        ];
    }

    /**
     * @param bool $allFields
     * @return array
     */
    public function toArray($allFields)
    {
        $result = [];
        $fieldNames = $allFields ? $this->getAllFields() : $this->getVisibleFields();
        foreach ($fieldNames as $fieldName) {
            $result[$fieldName] = $this->$fieldName;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray(false);
    }

    public function setFromArray($array, $resetAll)
    {
        foreach ($this->getAllFields() as $fieldName) {
            if (!$resetAll && !array_key_exists($fieldName, $array)) {
                continue;
            }

            $value = $array[$fieldName] ?? null;
            $type = $this->getFieldInfo($fieldName)['var'];
            switch ($type) {
                case 'bool':
                    $value = (bool)$value;
                    break;
                case 'bool|null':
                    if ($value !== null) {
                        $value = (bool)$value;
                    }
                    break;
            }
            $this->$fieldName = $value;
        }
    }

    /**
     * @param array $array
     */
    public function jsonUnserialize($array)
    {
        $this->setFromArray($array, true);
    }

    /**
     * @param array $array
     * @return static
     */
    public static function fromJson($array)
    {
        $className = static::class;
        /** @var static $row */
        $row = new $className();
        $row->jsonUnserialize($array);
        return $row;
    }

    /**
     * @return static|null
     */
    public function getOriginalRow()
    {
        return static::getInstance()->getRowById($this->id);
    }

    /**
     * @param string $fieldName
     * @param EntityUpdateAction|string $action
     * @param bool $isSkipped
     * @return BaseFieldHook|null
     */
    private function _getFieldHookObject($fieldName, $action, $isSkipped = false)
    {
        $fieldInfo = $this->getFieldInfo($fieldName);
        $hookName = $fieldInfo['hook'] ?? null;
        if ($hookName) {
            $hookClassName = __NAMESPACE__ . '\\FieldHook\\' . $hookName . 'FieldHook';
            /** @var BaseFieldHook $hook */
            $hook = new $hookClassName($this, $fieldName, $action);
            if ($hook->isActionSupported($isSkipped)) {
                return $hook;
            }
        }
        return null;
    }

    private function _add($disableHooks = false, $log = true, $allFields = false)
    {
        $guid = $this->id = $this->id ?: 'fake';

        $this->reportAction(EntityUpdateAction::INSERT);

        $data = [];

        $fieldNames = $allFields ? $this->getAllFields() : $this->getVisibleFields();
        foreach ($fieldNames as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::INSERT);
            if ($hook) {
                $hook->validate();
                if ($hook->shouldSkipField()) {
                    continue;
                }
                $hook->updateFieldValue();
            }

            $data[$fieldName] = $this->$fieldName;
        }

        Db::getInstance()->insertRow($this->getTableName(), $data, $log);

        $this->id = $id = (string)Db::getInstance()->getInsertedId();
        if ($this->_guidMap) {
            $this->_guidMap->add($guid, $id);
        }

        $instance = static::getInstance();
        if (isset($instance->_changes[$guid])) {
            $instance->_changes[$id] = $instance->_changes[$guid];
            unset($instance->_changes[$guid]);
        }
        foreach (AllEntities::getAllModels() as $instance) {
            foreach ($instance->_changes as $change) {
                foreach ($change->foreignRowChanges[get_class($this)] ?? [] as $foreignRowChange) {
                    if ($foreignRowChange->action === EntityUpdateAction::INSERT) {
                        if ($foreignRowChange->updateRow->id === $guid) {
                            $foreignRowChange->updateRow->id = $id;
                        }
                        if ($foreignRowChange->updateRow->id === '-' . $guid) {
                            $foreignRowChange->updateRow->id = '-' . $id;
                        }
                    }
                }
            }
        }
    }

    public function add($log = true, $allFields = false)
    {
        $this->_add(false, $log, $allFields);
        AllEntities::clearCache();
    }

    private function _update($updateFields, $disableHooks = false, $log = true)
    {
        $this->reportAction(EntityUpdateAction::UPDATE, $updateFields);

        $originalRow = $this->getOriginalRow();
        if (!$originalRow) {
            throw new BadRequestException('Row not found');
        }

        $updatesArray = [];

        foreach ($this->getVisibleFields() as $fieldName) {
            $fieldInfo = $this->getFieldInfo($fieldName);
            $isSkipped = !in_array($fieldName, $updateFields);

            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::UPDATE, $isSkipped);
            if ($hook) {
                $hook->validate();
            }

            if (isset($fieldInfo['readOnly'])) {
                continue;
            }

            if ($hook) {
                if ($hook->shouldSkipField()) {
                    continue;
                }
                if ($hook->updateFieldValue()) {
                    $isSkipped = false;
                }
            }

            if (!$isSkipped) {
                $updatesArray[$fieldName] = $this->$fieldName;
            }
        }

        if ($updatesArray) {
            Db::getInstance()->updateRow($this->getTableName(), abs($this->id), $updatesArray, $log);
        }
    }

    public function update($updateFields, $log = true)
    {
        $this->_update($updateFields, false, $log);
        AllEntities::clearCache();
    }

    private function _delete($disableHooks = false, $log = true)
    {
        $this->reportAction(EntityUpdateAction::DELETE);

        foreach ($this->getVisibleFields() as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::DELETE);
            if ($hook) {
                $hook->validate();
            }
        }

        Db::getInstance()->deleteRow($this->getTableName(), abs($this->id), $log);
    }

    public function delete($log = true)
    {
        $this->_delete($log);
        AllEntities::clearCache();
    }

    /**
     * @param EntityUpdateAction|string $action
     * @param bool $disableHooks
     * @param string[] $updateFields
     * @param bool $log
     */
    public function save($action, $disableHooks = false, $updateFields = [], $log = true)
    {
        switch ($action) {
            case EntityUpdateAction::INSERT:
                $this->_add($disableHooks, $log);
                break;
            case EntityUpdateAction::UPDATE:
                $this->_update($updateFields, $disableHooks, $log);
                break;
            case EntityUpdateAction::DELETE:
                $this->_delete($disableHooks, $log);
                break;
        }

        AllEntities::clearCache();
    }

    /**
     * @return string|null
     */
    public function getParentFieldName()
    {
        foreach ($this->getFieldsInfo() as $fieldName => $fieldInfo) {
            if (!empty($fieldInfo['parent'])) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * @param BaseEntity|string $foreignClassName
     * @return BaseEntity[]
     */
    public function getChildRows($foreignClassName)
    {
        /** @var BaseEntity $foreignModel */
        $foreignModel = $foreignClassName::getInstance();
        return $foreignModel->getRowsMap([$foreignModel->getParentFieldName()], null, true)[$this->id] ?? [];
    }

    protected function getSummarySeparator()
    {
        return ' ';
    }

    public function formatFieldValue($fieldName, $isHtml, User $recipient = null, $includeEntityName = true)
    {
        $fieldValue = $this->$fieldName;
        $fieldInfo = $this->getFieldInfo($fieldName);

        if (!empty($fieldInfo['foreignClass'])) {
            $foreignRow = $this->getForeignModel($fieldName)->getRowById($fieldValue);
            if (!$foreignRow) {
                return '';
            }
            $result = $foreignRow->getSummary($isHtml);
            if ($includeEntityName) {
                $foreignEntityName = $foreignRow->getEntityName($recipient);
                if ($foreignEntityName) {
                    $quot = $isHtml ? '&quot;' : '"';
                    $result = "$foreignEntityName $quot$result$quot";
                }
            }
            return $result;
        }

        if (is_string($fieldValue)) {
            $fieldValue = trim($fieldValue);
        }

        $fieldValue = array_search($fieldValue, $fieldInfo['format'], true) ?: $fieldValue;
        $fieldValue = (string)$fieldValue;

        return $isHtml ? htmlspecialchars($fieldValue) : $fieldValue;
    }

    /**
     * @param bool $isHtml
     * @param bool $isLink
     * @param string[] $excludedFieldNames
     * @return string
     */
    public function getSummary($isHtml, $isLink = true, $excludedFieldNames = [])
    {
        $summaryParts = [];

        foreach ($this->getFieldsInfo() as $fieldName => $fieldInfo) {
            if (!empty($fieldInfo['displayField']) && !in_array($fieldName, $excludedFieldNames)) {
                $fieldValue = $this->formatFieldValue($fieldName, $isHtml, null, false);
                if ($fieldValue !== '') {
                    $summaryParts[] = $fieldValue;
                }
            }
        }

        return implode($this->getSummarySeparator(), $summaryParts);
    }

    public function getSummaryWithoutParent($isHtml, $isLink = true)
    {
        return $this->getSummary($isHtml, $isLink, [$this->getParentFieldName()]);
    }

    /**
     * @param EntityUpdateAction|string $action
     * @param string[] $changedFields
     * @return RowChange
     */
    protected function createChangeObject($action, $changedFields = [])
    {
        return new RowChange($action, $this->getOriginalRow(), $this, $changedFields);
    }

    /**
     * @param EntityUpdateAction|string $action
     * @param bool $override
     * @param string[] $changedFields
     * @return RowChange
     */
    protected function getOrCreateChangeObject($action, $override, $changedFields = [])
    {
        $instance = static::getInstance();
        $change = $instance->_changes[$this->id] = $instance->_changes[$this->id] ?? $this->createChangeObject($action, $changedFields);
        if ($override) {
            $change->action = $action;
            $change->originalRow = $this->getOriginalRow();
            $change->updateRow = $this;
            $change->changedFields = $changedFields;
        }
        return $change;
    }

    /**
     * @param EntityUpdateAction|string $action
     * @param string[] $changedFields
     */
    public function reportAction($action, $changedFields = [])
    {
        $parentFieldName = $this->getParentFieldName();
        if ($parentFieldName) {
            $parentId = $this->$parentFieldName ?? $this->getOriginalRow()->$parentFieldName;
            $parentRow = $this->getForeignModel($parentFieldName)->getRowById($parentId);
            if ($parentRow) {
                $parentRow
                    ->getOrCreateChangeObject(EntityUpdateAction::UPDATE, false)
                    ->addForeignRowChange(get_class($this), $this->createChangeObject($action, $changedFields));
            }
        } else {
            $this->getOrCreateChangeObject($action, true, $changedFields);
        }
    }

    public function getChanges()
    {
        if ($this !== static::getInstance()) {
            throw new InternalServerErrorException();
        }
        return $this->_changes;
    }

    public function setChanges($changes)
    {
        if ($this !== static::getInstance()) {
            throw new InternalServerErrorException();
        }
        $this->_changes = $changes;
    }

    /**
     * @param User|null $recipient
     * @return string|null
     */
    public function getEntityName(User $recipient = null)
    {
        return null;
    }

    public function getChangeSummary(RowChange $change, User $recipient, $isHtml)
    {
        $summary = EntityUpdateAction::getActionName($change->action);
        $entityName = $this->getEntityName($recipient);
        if ($entityName) {
            $summary .= ' ' . $entityName;
        }
        $summary .= ' ' . $change->getInfoRow()->getSummary($isHtml, $change->action !== EntityUpdateAction::DELETE);
        return $summary;
    }

    protected function formatChangeFieldWrapper($displayName, $html, $isMultiline = false, $force = false)
    {
        if ($html === '' && !$force) {
            return '';
        }

        if ($html !== '' && $isMultiline) {
            $html = Html::multiline($html);
        }

        if ($displayName) {
            $html = $html === ''
                ? Html::bold($displayName . '.')
                : Html::bold($displayName . ':') . ' ' . $html;
            $html = Html::section($html);
        }

        if ($html !== '') {
            $html .= "\n";
        }

        return $html;
    }

    protected function formatChangeField(RowChange $change, $fieldName, $displayName = '', $showForDelete = false)
    {
        if ($change->action === EntityUpdateAction::DELETE && !$showForDelete) {
            return '';
        }
        $fieldChange = $change->getFieldChange($fieldName);
        if (!$fieldChange) {
            return '';
        }

        $fieldInfo = $this->getFieldInfo($fieldName);

        if ($change->action !== EntityUpdateAction::UPDATE) {
            $html = $fieldChange->fromHtml ?: $fieldChange->toHtml;
        } else {
            /** @var StringDiffOperation[] $diff */
            if (!empty($fieldInfo['diffable'])) {
                $needEscape = true;
                $diff = Differ::getInstance()->getDiff($fieldChange->fromText, $fieldChange->toText);
            } else {
                $needEscape = false;
                $diff = [];
                if ($fieldChange->fromHtml) {
                    $diff[] = new StringDiffOperation(StringDiffOperation::DELETE, $fieldChange->fromHtml);
                }
                if ($fieldChange->toHtml) {
                    $diff[] = new StringDiffOperation(StringDiffOperation::INSERT, $fieldChange->toHtml);
                }
            }

            $html = '';
            foreach ($diff as $part) {
                $content = $needEscape ? htmlspecialchars($part->content) : $part->content;
                switch ($part->operation) {
                    case StringDiffOperation::DELETE:
                        $content = Html::diffDelete($content);
                        break;
                    case StringDiffOperation::INSERT:
                        $content = Html::diffAdd($content);
                        break;
                }
                $html .= $content;
            }
        }

        return $this->formatChangeFieldWrapper($displayName, $html, !empty($fieldInfo['multiline']));
    }

    public function formatForeignTableChanges(RowChange $change, $foreignClassName, $displayName)
    {
        if ($change->action === EntityUpdateAction::DELETE) {
            return '';
        }

        if (empty($change->foreignRowChanges[$foreignClassName])) {
            return '';
        }

        $parts = array_map(
            function(BaseEntity $foreignRow) {
                return $foreignRow->getSummaryWithoutParent(true);
            },
            Map::map($this->getChildRows($foreignClassName))
        );

        foreach ($change->foreignRowChanges[$foreignClassName] as $foreignRowChange) {
            /** @var static $infoRow */
            $infoRow = $foreignRowChange->getInfoRow();
            $content = $infoRow->getSummaryWithoutParent(true);
            if ($change->action === EntityUpdateAction::UPDATE) {
                switch ($foreignRowChange->action) {
                    case EntityUpdateAction::DELETE:
                        $parts[$infoRow->id] = Html::diffDelete($content);
                        break;
                    case EntityUpdateAction::INSERT:
                        $parts[$infoRow->id] = Html::diffAdd($content);
                        break;
                    case EntityUpdateAction::UPDATE:
                        /** @var static $originalRow */
                        $originalRow = $foreignRowChange->originalRow;
                        $prevContent = $originalRow->getSummaryWithoutParent(true);
                        $parts[$infoRow->id] = Html::diffDelete($prevContent) . Html::diffAdd($content);
                        break;
                }
            } else {
                $parts[$infoRow->id] = $content;
            }
        }

        return $this->formatChangeFieldWrapper($displayName, implode(', ', $parts));
    }

    /**
     * @param RowChange $change
     * @param User $recipient
     * @return string
     */
    public function formatChange(RowChange $change, User $recipient)
    {
        throw new InternalServerErrorException();
    }

    /**
     * @param RowChange $change
     * @param User $recipient
     * @return string|null
     */
    public function getNotificationReason(RowChange $change, User $recipient)
    {
        return null;
    }
}
