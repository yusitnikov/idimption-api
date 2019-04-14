<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Db;
use Idimption\Entity\EntityUpdateAction;

class AutoIncrementFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function shouldSkipField()
    {
        return true;
    }

    public function updateFieldValueAfterSave()
    {
        if ($this->_action === EntityUpdateAction::INSERT) {
            $guid = $this->_newFieldValue;
            $this->_newFieldValue = $id = (string)Db::getInsertedId();
            if ($guid) {
                $guidMap = $this->_row->getGuidMap();
                if ($guidMap) {
                    $guidMap->add($guid, $id);
                }
            }
        }
    }
}
