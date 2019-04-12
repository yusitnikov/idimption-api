<?php

namespace Idimption\Entity\FieldHook;

use Idimption\App;

class CurrentTimeFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function updateFieldValue()
    {
        $this->_newFieldValue = App::getInstance()->getStartTime();
        return true;
    }
}
