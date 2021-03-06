<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;

class PasswordHashFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return !$isSkipped && !empty($this->_newFieldValue);
    }

    public function updateFieldValue()
    {
        $this->_newFieldValue = Auth::getPasswordHash($this->_newFieldValue);
        return true;
    }
}
