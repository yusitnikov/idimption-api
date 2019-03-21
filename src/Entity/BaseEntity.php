<?php

namespace Idimption\Entity;

use Idimption\Db;
use Idimption\Entity\FieldHook\BaseFieldHook;
use Idimption\Map;

abstract class BaseEntity implements \JsonSerializable
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
        $sql = "
            SELECT *
            FROM " . Db::escapeName($this->_viewName) . "
            " . (in_array('id', $this->getAllFields()) ? 'ORDER BY id' : '') . "
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
        $class = new \ReflectionClass($this);
        return array_map(
            function(\ReflectionProperty $property) {
                return $property->name;
            },
            $class->getProperties(\ReflectionProperty::IS_PUBLIC)
        );
    }

    public function getFieldInfo($fieldName)
    {
        $info = [];
        $field = new \ReflectionProperty($this, $fieldName);

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

        if (isset($info['foreignClass'])) {
            /** @var self|string $foreignKeyClassName */
            $foreignKeyClassName = $info['foreignClass'] === 'static' ? get_class($this) : __NAMESPACE__ . '\\' . $info['foreignClass'];
            $info['foreignTable'] = $foreignKeyClassName::getInstance()->getTableName();
        }

        return $info;
    }

    public function getFieldsInfo()
    {
        $result = [];
        foreach ($this->getAllFields() as $fieldName) {
            $result[$fieldName] = $this->getFieldInfo($fieldName);
        }
        return $result;
    }

    public function getSchema()
    {
        return [
            'tableName' => $this->_tableName,
            'className' => get_class($this),
            'fields' => $this->getAllFields(),
            'fieldsInfo' => $this->getFieldsInfo(),
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->getAllFields() as $fieldName) {
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
            $this->$fieldName = $array[$fieldName] ?? null;
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

    private function _add($disableHooks = false)
    {
        $fieldNameSqlParts = [];
        $fieldValueSqlParts = [];

        foreach ($this->getAllFields() as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::INSERT);
            if ($hook) {
                if ($hook->shouldSkipField()) {
                    continue;
                }
                $hook->updateFieldValue();
            }

            $fieldNameSqlParts[] = Db::escapeName($fieldName);
            $fieldValueSqlParts[] = Db::escapeValue($this->$fieldName);
        }

        Db::exec('
            INSERT INTO ' . Db::escapeName($this->getTableName()) . '
            (' . implode(', ', $fieldNameSqlParts) . ')
            VALUES (' . implode(', ', $fieldValueSqlParts) . ')
        ', [
            'tableName' => $this->getTableName(),
            'fields' => $fieldNameSqlParts,
            'values' => $fieldValueSqlParts,
        ]);

        foreach ($this->getAllFields() as $fieldName) {
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::INSERT);
            if ($hook) {
                $hook->updateFieldValueAfterSave();
            }
        }
    }

    private function _update($disableHooks = false, $updateFields = [])
    {
        $updateSqlParts = [];
        $updatesArray = [];

        foreach ($this->getAllFields() as $fieldName) {
            $fieldInfo = $this->getFieldInfo($fieldName);
            $isPrimaryKey = $fieldName === 'id';
            if (isset($fieldInfo['readOnly']) && !$isPrimaryKey) {
                continue;
            }
            $isSkipped = $updateFields && !in_array($fieldName, $updateFields);

            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::UPDATE, $isSkipped);
            if ($hook) {
                if ($hook->shouldSkipField()) {
                    continue;
                }
                $hook->updateFieldValue();
                $isSkipped = false;
            }

            if (!$isSkipped && !$isPrimaryKey) {
                $value = $this->$fieldName;
                $updateSqlParts[] = Db::escapeName($fieldName) . ' = ' . Db::escapeValue($value);
                $updatesArray[$fieldName] = $value;
            }
        }

        if ($updateSqlParts) {
            /** @noinspection SqlResolve */
            Db::exec('
                UPDATE ' . Db::escapeName($this->getTableName()) . '
                SET ' . implode(', ', $updateSqlParts) . '
                WHERE id = ' . Db::escapeValue($this->id) . '
            ', [
                'tableName' => $this->getTableName(),
                'updates' => $updatesArray,
                'id' => $this->id,
            ]);
        }

        foreach ($this->getAllFields() as $fieldName) {
            $isSkipped = $updateFields && !in_array($fieldName, $updateFields);
            $hook = $disableHooks ? null : $this->_getFieldHookObject($fieldName, EntityUpdateAction::UPDATE, $isSkipped);
            if ($hook) {
                $hook->updateFieldValueAfterSave();
            }
        }
    }

    private function _delete()
    {
        /** @noinspection SqlResolve */
        Db::exec('
          DELETE
          FROM ' . Db::escapeName($this->getTableName()) . '
          WHERE id = ' . Db::escapeValue($this->id) . '
        ', [
            'tableName' => $this->getTableName(),
            'id' => $this->id,
        ]);
    }

    public function delete()
    {
        $this->_delete();
        AllEntities::clearCache();
    }

    /**
     * @param EntityUpdateAction|string $action
     * @param bool $disableHooks
     * @param string[] $updateFields
     */
    public function save($action, $disableHooks = false, $updateFields = [])
    {
        switch ($action) {
            case EntityUpdateAction::INSERT:
                $this->_add($disableHooks);
                break;
            case EntityUpdateAction::UPDATE:
                $this->_update($disableHooks, $updateFields);
                break;
            case EntityUpdateAction::DELETE:
                $this->_delete();
                break;
        }

        /*
         * TODO:
         * - get updated row from the DB
         * - ignore read-only fields on update
         * - primary key fields could be changed on update, but they still should be part of the WHERE clause
         */
        AllEntities::clearCache();
    }
}
