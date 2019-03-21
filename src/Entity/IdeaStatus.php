<?php

namespace Idimption\Entity;

class IdeaStatus extends BaseEntity
{
    use ReferenceIdFieldTrait, CommonTextFieldsTrait;

    public function __construct()
    {
        parent::__construct('ideastatus');
    }
}
