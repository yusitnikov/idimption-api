<?php

namespace Idimption\Entity;

trait TagIdFieldTrait
{
    /**
     * @var int
     * @foreignClass Tag
     * @displayField
     */
    public $tagId;

    /**
     * @return Tag
     */
    public function getTag()
    {
        return Tag::getInstance()->getRowById($this->tagId);
    }
}
