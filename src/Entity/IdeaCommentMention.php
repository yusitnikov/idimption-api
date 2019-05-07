<?php

namespace Idimption\Entity;

class IdeaCommentMention extends BaseEntity
{
    use UserIdLinkFieldTrait;

    /**
     * @var int
     * @foreignClass IdeaComment
     * @parent
     * @displayField
     * @hook IdeaCommentId
     */
    public $ideaCommentId;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideacommentmention');
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

    protected function getSummarySeparator()
    {
        return ' to ';
    }
}
