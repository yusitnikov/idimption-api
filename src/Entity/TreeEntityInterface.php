<?php

namespace Idimption\Entity;

interface TreeEntityInterface
{
    /**
     * @return int|null
     */
    public function getParentId();

    /**
     * @return static|null
     */
    public function getParent();

    /**
     * @return static[][]
     */
    public function getAllRowsByParentId();

    /**
     * @param int $parentId
     * @return static[]
     */
    public function getRowsByParentId($parentId);

    /**
     * @return ParentIdFieldTrait[]
     */
    public function getChildren();
}
