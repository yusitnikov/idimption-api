<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Auth;
use Idimption\Entity\EntityUpdateAction;
use Idimption\Exception\AccessDeniedException;

trait UserIdFieldHookValidateTrait
{
    public function validate()
    {
        if ($this->_action !== EntityUpdateAction::INSERT && !Auth::canEditUsersData($this->_currentFieldValue)) {
            throw new AccessDeniedException('Access denied');
        }
    }
}
