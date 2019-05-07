<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Entity\BaseEntity;
use Idimption\Entity\EntityUpdateAction;

abstract class BaseFieldHook
{
    /** @var BaseEntity */
    protected $_row;

    /** @var string */
    protected $_fieldName;

    /** @var mixed */
    protected $_newFieldValue;

    /** @var mixed */
    protected $_currentFieldValue;

    /** @var EntityUpdateAction|string */
    protected $_action;

    /**
     * BaseFieldHook constructor.
     *
     * @param BaseEntity $row
     * @param string $fieldName
     * @param EntityUpdateAction|string $action
     */
    public function __construct(BaseEntity $row, $fieldName, $action)
    {
        $this->_row = $row;
        $this->_fieldName = $fieldName;
        $this->_newFieldValue =& $row->$fieldName;
        if ($action !== EntityUpdateAction::INSERT) {
            $this->_currentFieldValue = $row->getOriginalRow()->$fieldName;
        }
        $this->_action = $action;
    }

    /**
     * @return BaseEntity
     */
    protected function _getEntityModel()
    {
        return $this->_row::getInstance();
    }

    /**
     * @return array
     */
    protected function _getFieldInfo()
    {
        return $this->_getEntityModel()->getFieldInfo($this->_fieldName);
    }

    /**
     * @param bool $isSkipped
     * @return bool
     */
    public abstract function isActionSupported($isSkipped = false);

    public function validate()
    {
    }

    public function shouldSkipField()
    {
        return false;
    }

    public function updateFieldValue()
    {
        return false;
    }
}
