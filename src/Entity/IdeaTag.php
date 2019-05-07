<?php

namespace Idimption\Entity;

class IdeaTag extends BaseEntity
{
    use IdeaIdParentFieldTrait, TagIdFieldTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideatag');
    }
}
