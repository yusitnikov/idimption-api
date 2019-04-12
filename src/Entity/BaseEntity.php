<?php

namespace Idimption\Entity;

use Idimption\Db;
use Idimption\Entity\FieldHook\BaseFieldHook;
use Idimption\Map;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseEntity implements JsonSerializable
{
    /**
     * @return static
     */
    public static function getInstance()
    {
        static $instances = [];
        $class = static::class;
        return $instances[$class] = $instances[$class] ?? new $class();
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

    /**
     * @var int
     * @hook AutoIncrement
     * @readOnly
     */
    public $id;

    protected function __construct($tableName, $viewName = null)
    {
        $this->_tableName = $tableName;
        $this->_viewName = $viewName ?? $tableName;
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
        /** @noinspection SqlResolve */
        $sql = "
            SELECT *
            FROM " . Db::escapeName($this->_viewName) . "
            ORDER BY id
        ";
        return $this->_allRowsCache = $this->_allRowsCache ?? array_map([get_class($this), 'fromJson'], Db::select($sql));
    }

    /**
     * @param string[] $fields
     * @param string|null $resultField
     * @param bool $multiple
     * @return array
     */
    public function getRowsMap($fields = ['id'], $resultField = null, $multiple = false)
    {
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
     * @param int|string $id
     * @return static|null
     */
    public function getRowById($id)
    {
        return $this->getAllRowsById()[$id] ?? null;
    }

    public function clearCache()
    {
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
        return $this->getAllFields();
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
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->getVisibleFields() as $fieldName) {
            $result[$fieldName] = $this->$fieldName;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param array $array
     */
    public function jsonUnserialize($array)
    {
        foreach ($this->getAllFields() as $fieldName) {
            $value = $array[$fieldName] ?? null;
            $type = $this->getFieldInfo($fieldName)['var'];
            if ($type === 'bool') {
                $value = (bool)$value;
            }
            $this->$fieldName = $value;
        }
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

    private function _add($disableHooks = false, $log = true)
    {
        $data = [];

        foreach ($this->getVisibleFields() as $fieldName) {
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

        Db::insertRow($this->getTableName(), $data, $log);

        foreach ($this->getVisibleFields() as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::INSERT);
            if ($hook) {
                $hook->updateFieldValueAfterSave();
            }
        }
    }

    public function add($log = true)
    {
        $this->_add(false, $log);
        AllEntities::clearCache();
    }

    private function _update($disableHooks = false, $updateFields = [], $log = true)
    {
        $updatesArray = [];

        foreach ($this->getVisibleFields() as $fieldName) {
            $fieldInfo = $this->getFieldInfo($fieldName);
            $isSkipped = $updateFields && !in_array($fieldName, $updateFields);

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
            Db::updateRow($this->getTableName(), $this->id, $updatesArray, $log);
        }

        foreach ($this->getVisibleFields() as $fieldName) {
            $isSkipped = $updateFields && !in_array($fieldName, $updateFields);
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::UPDATE, $isSkipped);
            if ($hook) {
                $hook->updateFieldValueAfterSave();
            }
        }
    }

    public function update($updateFields = [], $log = true)
    {
        $this->_update(false, $updateFields, $log);
        AllEntities::clearCache();
    }

    private function _delete($disableHooks = false, $log = true)
    {
        foreach ($this->getVisibleFields() as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::DELETE);
            if ($hook) {
                $hook->validate();
            }
        }

        Db::deleteRow($this->getTableName(), $this->id, $log);
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
                $this->_update($disableHooks, $updateFields, $log);
                break;
            case EntityUpdateAction::DELETE:
                $this->_delete($disableHooks, $log);
                break;
        }

        /*
         * TODO:
         * - get updated row from the DB
         * - primary key fields could be changed on update, but they still should be part of the WHERE clause
         */
        AllEntities::clearCache();
    }
}
