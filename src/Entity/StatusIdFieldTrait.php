<?php

namespace Idimption\Entity;

trait StatusIdFieldTrait
{
    /**
     * @var int
     * @foreignClass IdeaStatus
     */
    public $statusId;

    /**
     * @return IdeaStatus
     */
    public function getStatus()
    {
        return IdeaStatus::getInstance()->getRowById($this->statusId);
    }
}
