<?php

namespace Idimption\Entity;

class Category extends BaseEntity
{
    use ParentIdFieldTrait, ReferenceIdFieldTrait, CommonTextFieldsTrait;

    /**
     * @var int|null
     * @foreignClass static
     */
    public $parentId;

    public function __construct()
    {
        parent::__construct('category');
    }
}
