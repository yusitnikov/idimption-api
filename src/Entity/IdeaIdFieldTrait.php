<?php

namespace Idimption\Entity;

trait IdeaIdFieldTrait
{
    /**
     * @var int
     * @foreignClass Idea
     */
    public $ideaId;

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

    /**
     * @param int $ideaId
     * @param static[] $newRows
     */
    public function syncRowsByIdeaId($ideaId, $newRows)
    {
        $existingRows = $this->getRowsByIdeaId($ideaId);
        // TODO
    }
}
