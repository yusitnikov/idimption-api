<?php

namespace Idimption\Entity;

class IdeaSubscription extends BaseEntity
{
    use UserIdFieldTrait, IdeaIdFieldTrait;

    /**
     * @var int
     * @foreignClass Idea
     */
    public $ideaId;

    /**
     * @var bool
     */
    public $included;

    public function __construct()
    {
        parent::__construct('ideasubscription');
    }

    public function allowAnonymousCreate()
    {
        return false;
    }
}
