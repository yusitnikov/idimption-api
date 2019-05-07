<?php

namespace Idimption\Entity;

trait IdeaIdParentFieldTrait
{
    use RelationEntityTrait, FieldsOrderTrait;
    use IdeaIdFieldTrait;

    /**
     * @var int
     * @foreignClass Idea
     * @parent
     * @displayField
     * @hook IdeaId
     */
    public $ideaId;

    protected function getFieldsOrder()
    {
        return ['ideaId'];
    }

    protected function getSummarySeparator()
    {
        return ' - ';
    }
}
