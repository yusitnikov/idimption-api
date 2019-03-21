<?php

namespace Idimption\Entity;

class Idea extends BaseEntity
{
    use ReferenceIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, CommonTextFieldsTrait, StatusIdFieldTrait;

    public function __construct()
    {
        parent::__construct('idea');
    }
}
