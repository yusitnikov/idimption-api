<?php

namespace Idimption\Entity;

class IdeaCategory extends BaseEntity
{
    use RelationEntityTrait, IdeaIdFieldTrait, CategoryIdFieldTrait;

    public function __construct()
    {
        parent::__construct('ideacategory');
    }
}
