<?php

namespace Idimption\Entity;

trait IdeaIdFieldTrait
{
    /**
     * @var int
     * @foreignClass Idea
     * @displayField
     */
    public $ideaId;

    public function allowAnonymousCreate()
    {
        return true;
    }

    /**
     * @return Idea
     */
    public function getIdea()
    {
        return Idea::getInstance()->getRowById($this->ideaId);
    }

    /**
     * @return static[][]
     */
    public function getAllRowsByIdeaId()
    {
        return $this->getRowsMap(['ideaId'], null, true);
    }

    /**
     * @param int $ideaId
     * @return static[]
     */
    public function getRowsByIdeaId($ideaId)
    {
        return $this->getAllRowsByIdeaId()[$ideaId] ?? [];
    }
}
