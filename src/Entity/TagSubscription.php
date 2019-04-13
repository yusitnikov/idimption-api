<?php

namespace Idimption\Entity;

class TagSubscription extends BaseEntity
{
    use UserIdFieldTrait, TagIdFieldTrait;

    /**
     * @var bool
     */
    public $included;

    public function __construct()
    {
        parent::__construct('tagsubscription');
    }
}
