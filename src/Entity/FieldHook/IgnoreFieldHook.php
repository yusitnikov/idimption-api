<?php

namespace Idimption\Entity\FieldHook;

class IgnoreFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function shouldSkipField()
    {
        return true;
    }
}
