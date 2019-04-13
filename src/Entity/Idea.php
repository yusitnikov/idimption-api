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

    public function allowAnonymousCreate()
    {
        return true;
    }

    public function toArray()
    {
        $result = parent::toArray();
        $result['priority'] = $this->priority ?? $this->id;
        return $result;
    }
}
