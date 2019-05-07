<?php

namespace Idimption\Entity;

class IdeaRelation extends BaseEntity
{
    use IdeaIdParentFieldTrait, RelationIdFieldTrait;

    /**
     * @var int
     * @foreignClass Idea
     * @displayField
     * @hook IdeaId
     */
    public $dstIdeaId;

    public function __construct($data = [])
    {
        parent::__construct($data, 'idearelation', 'idearelationfull');
    }

    /**
     * @return Idea
     */
    public function getDstIdea()
    {
        return Idea::getInstance()->getRowById($this->dstIdeaId);
    }

    protected function getFieldsOrder()
    {
        return ['ideaId', 'relationId', 'dstIdeaId'];
    }

    protected function getSummarySeparator()
    {
        return ' ';
    }

    public function getOpposite()
    {
        $opposite = clone $this;
        $opposite->id = '-' . $this->id;
        $opposite->ideaId = $this->dstIdeaId;
        $opposite->dstIdeaId = $this->ideaId;
        if ($this->relationId) {
            $opposite->relationId = $this->getRelation()->oppositeId ?? $this->relationId;
        }
        return $opposite;
    }

    private function parentReportAction($action, $changedFields)
    {
        parent::reportAction($action, $changedFields);
    }

    public function reportAction($action, $changedFields = [])
    {
        $this->parentReportAction($action, $changedFields);
        $this->getOpposite()->parentReportAction($action, $changedFields);
    }
}
