<?php

namespace Idimption\Entity\FieldHook;

use Idimption\App;

class UserIdFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function updateFieldValue()
    {
        $this->_fieldValue = App::getInstance()->getUserId();
    }
}
