<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;
use Idimption\Entity\EntityUpdateAction;
use Idimption\Entity\UserIdFieldTrait;
use Idimption\Exception\AccessDeniedException;
use Idimption\Exception\BadRequestException;

class ForeignUserIdFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function validate()
    {
        /** @var UserIdFieldTrait $foreignRow */
        $foreignRow = $this->_getEntityModel()->getForeignModel($this->_fieldName)->getRowById($this->_currentFieldValue);
        if (!$foreignRow) {
            if ($this->_action === EntityUpdateAction::INSERT) {
                return;
            } else {
                throw new BadRequestException('Row not found');
            }
        }

        if (!Auth::canEditUsersData($foreignRow->userId)) {
            throw new AccessDeniedException('Access denied');
        }
    }
}
