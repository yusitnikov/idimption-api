<?php

namespace Idimption\Entity\FieldHook;

use Idimption\App;
use Idimption\Auth;
use Idimption\Entity\Idea;
use Idimption\Exception\AccessDeniedException;
use Idimption\Exception\BadRequestException;

class IdeaIdFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function validate()
    {
        $ideaId = $this->_currentFieldValue ?: $this->_newFieldValue;

        /** @var Idea $idea */
        $idea = Idea::getInstance()->getRowById($ideaId);
        if (!$idea) {
            throw new BadRequestException('Row not found');
        }

        if ($idea->isJustCreated()) {
            return;
        }

        if (!Auth::canEditUsersData($idea->userId)) {
            throw new AccessDeniedException('Access denied');
        }

        $currentTime = App::getInstance()->getStartTime();
        if ($idea->updatedAt != $currentTime) {
            $idea->update(['updatedDt'], false);
        }
    }
}
