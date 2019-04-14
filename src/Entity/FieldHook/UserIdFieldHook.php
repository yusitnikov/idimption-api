<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;

class UserIdFieldHook extends BaseFieldHook
{
    use UserIdFieldHookValidateTrait;

    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function updateFieldValue()
    {
        $this->_newFieldValue = Auth::getLoggedInUserId();
        return true;
    }
}
