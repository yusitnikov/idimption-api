<?php

namespace Idimption\Entity;

class IdeaRelation extends BaseEntity
{
    use RelationEntityTrait, IdeaIdFieldTrait, RelationIdFieldTrait;

    /**
     * @var int
     * @foreignClass Idea
     * @hook ForeignUserId
     */
    public $dstIdeaId;

    public function __construct()
    {
        parent::__construct('idearelation', 'idearelationfull');
    }

    /**
     * @return Idea
     */
    public function getDstIdea()
    {
        return Idea::getInstance()->getRowById($this->dstIdeaId);
    }
}
