<?php

namespace Idimption\Entity;

trait CategoryIdFieldTrait
{
    /**
     * @var int
     * @foreignClass Category
     */
    public $categoryId;

    /**
     * @return Category
     */
    public function getCategory()
    {
        return Category::getInstance()->getRowById($this->categoryId);
    }
}
