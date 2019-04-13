<?php

namespace Idimption\Entity;

class IdeaCommentMention extends BaseEntity
{
    use UserIdFieldTrait;

    /**
     * @var int
     * @foreignClass IdeaComment
     * @hook IdeaCommentId
     */
    public $ideaCommentId;

    /**
     * @var string
     * @foreignClass User
     * @readOnly
     */
    public $userId;

    public function __construct()
    {
        parent::__construct('ideacommentmention');
    }

    public function allowAnonymousCreate()
    {
        return true;
    }

    /**
     * @return IdeaComment
     */
    public function getIdeaComment()
    {
        return IdeaComment::getInstance()->getRowById($this->ideaCommentId);
    }
}
