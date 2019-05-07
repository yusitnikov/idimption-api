<?php

namespace Idimption\Entity;

trait RelationIdFieldTrait
{
    /**
     * @var int
     * @foreignClass Relation
     * @displayField
     */
    public $relationId;

    /**
     * @return Relation
     */
    public function getRelation()
    {
        return Relation::getInstance()->getRowById($this->relationId);
    }
}
