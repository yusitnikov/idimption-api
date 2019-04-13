<?php

namespace Idimption\Entity;

class CategorySubscription extends BaseEntity
{
    use UserIdFieldTrait, CategoryIdFieldTrait;

    /**
     * @var bool|null
     */
    public $included;

    public function __construct()
    {
        parent::__construct('categorysubscription');
    }
}
