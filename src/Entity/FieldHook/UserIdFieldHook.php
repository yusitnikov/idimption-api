<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;
use Idimption\Entity\EntityUpdateAction;
use Idimption\Entity\User;
use Idimption\Exception\AccessDeniedException;

class UserIdFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function validate()
    {
        if ($this->_action !== EntityUpdateAction::INSERT) {
            $userId = Auth::getLoggedInUserId();
            if (!$userId || $this->_currentFieldValue !== $userId) {
                throw new AccessDeniedException('Access denied');
            }
        }
    }

    public function updateFieldValue()
    {
        if ($this->_row instanceof User) {
            return false;
        } else {
            $this->_newFieldValue = Auth::getLoggedInUserId();
            return true;
        }
    }
}
