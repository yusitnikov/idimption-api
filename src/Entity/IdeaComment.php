<?php

namespace Idimption\Entity;

class IdeaComment extends BaseEntity
{
    use ParentIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, IdeaIdFieldTrait
    {
        UserIdFieldTrait::allowAnonymousCreate insteadof IdeaIdFieldTrait;
    }

    /**
     * @var int
     * @foreignClass Idea
     */
    public $ideaId;

    /**
     * @var string
     * @displayField
     */
    public $message = '';

    public function __construct()
    {
        parent::__construct('ideacomment');
    }
}
