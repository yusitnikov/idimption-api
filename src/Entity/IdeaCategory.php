<?php

namespace Idimption\Entity;

class IdeaCategory extends BaseEntity
{
    use IdeaIdParentFieldTrait, CategoryIdFieldTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideacategory');
    }
}
