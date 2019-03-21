<?php

namespace Idimption\Entity;

trait ParentIdFieldTrait
{
    /**
     * @var int|null
     * @foreignClass static
     */
    public $parentId;

    /**
     * @return static|null
     */
    public function getParent()
    {
        return $this->parentId ? $this->getRowById($this->parentId) : null;
    }

    /**
     * @return static[][]
     */
    public function getAllRowsByParentId()
    {
        return $this->getRowsMap(['parentId'], null, true);
    }

    /**
     * @param int $parentId
     * @return static[]
     */
    public function getRowsByParentId($parentId)
    {
        return $this->getAllRowsByParentId()[$parentId] ?? [];
    }

    /**
     * @return ParentIdFieldTrait[]
     */
    public function getChildren()
    {
        return $this->getRowsByParentId($this->id);
    }
}
