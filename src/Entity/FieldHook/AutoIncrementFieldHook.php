<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Db;
use Idimption\Entity\EntityUpdateAction;

class AutoIncrementFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return $this->_action === EntityUpdateAction::INSERT;
    }

    public function shouldSkipField()
    {
        return true;
    }

    public function updateFieldValueAfterSave()
    {
        $guid = $this->_fieldValue;
        $this->_fieldValue = $id = (string)Db::getInsertedId();
        $guidMap = $this->_row->getGuidMap();
        if ($guidMap) {
            $guidMap->add($guid, $id);
        }
    }
}
