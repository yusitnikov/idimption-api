<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;
use Idimption\Entity\EntityUpdateAction;

class PasswordHashFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return !$isSkipped && !empty($this->_newFieldValue);
    }

    public function shouldSkipField()
    {
        return $this->_action !== EntityUpdateAction::UPDATE;
    }

    public function updateFieldValue()
    {
        $this->_newFieldValue = Auth::getPasswordHash($this->_newFieldValue);
        return true;
    }
}
