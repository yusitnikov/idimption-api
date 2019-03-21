<?php

namespace Idimption\Entity\FieldHook;

class CurrentTimeFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function updateFieldValue()
    {
        $this->_fieldValue = time();
    }
}
