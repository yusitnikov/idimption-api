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
    protected $_fieldValue;

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
        $this->_fieldValue =& $row->$fieldName;
        $this->_action = $action;
    }

    /**
     * @param bool $isSkipped
     * @return bool
     */
    public abstract function isActionSupported($isSkipped = false);

    public function shouldSkipField()
    {
        return false;
    }

    public function updateFieldValue()
    {
    }

    public function updateFieldValueAfterSave()
    {
    }
}
