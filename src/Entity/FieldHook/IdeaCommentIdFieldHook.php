<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Entity\EntityUpdateAction;
use Idimption\Entity\IdeaComment;
use Idimption\Exception\AccessDeniedException;
use Idimption\Exception\BadRequestException;

class IdeaCommentIdFieldHook extends BaseFieldHook
{
    public function isActionSupported($isSkipped = false)
    {
        return true;
    }

    public function validate()
    {
        if ($this->_action !== EntityUpdateAction::INSERT) {
            throw new AccessDeniedException();
        }

        /** @var IdeaComment $comment */
        $comment = IdeaComment::getInstance()->getRowById($this->_newFieldValue);
        if (!$comment) {
            throw new BadRequestException('Row not found');
        }
        if (!$comment->isJustCreated()) {
            throw new AccessDeniedException();
        }
    }
}
