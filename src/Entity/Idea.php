<?php

namespace Idimption\Entity;

class Idea extends BaseEntity
{
    use ReferenceIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, CommonTextFieldsTrait, StatusIdFieldTrait;

    /**
     * @var double|null
     */
    public $priority;

    public function __construct()
    {
        parent::__construct('idea');
    }
}
