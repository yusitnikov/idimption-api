<?php

namespace Idimption\Entity;

class IdeaVote extends BaseEntity
{
    use IdeaIdFieldTrait, UserIdFieldTrait;

    /**
     * @var int
     * @foreignClass Idea
     */
    public $ideaId;

    /**
     * @var bool
     */
    public $isPositive;

    public function __construct()
    {
        parent::__construct('ideavote');
    }

    public function allowAnonymousCreate()
    {
        return false;
    }
}
