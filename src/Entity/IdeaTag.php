<?php

namespace Idimption\Entity;

class IdeaTag extends BaseEntity
{
    use RelationEntityTrait, IdeaIdFieldTrait, TagIdFieldTrait;

    public function __construct()
    {
        parent::__construct('ideatag');
    }
}
