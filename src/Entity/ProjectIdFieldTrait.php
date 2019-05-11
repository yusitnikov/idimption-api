<?php

namespace Idimption\Entity;

trait ProjectIdFieldTrait
{
    /**
     * @var int|null
     * @foreignClass Idea
     * @hook ProjectId
     */
    public $projectId;

    /**
     * @return Idea
     */
    public function getProject()
    {
        return Idea::getInstance()->getRowById($this->projectId);
    }

    protected function formatProjectChange(RowChange $change)
    {
        if ($change->action === EntityUpdateAction::DELETE) {
            return '';
        }

        return $this->formatChangeField($change, 'projectId', 'Project');
    }
}
