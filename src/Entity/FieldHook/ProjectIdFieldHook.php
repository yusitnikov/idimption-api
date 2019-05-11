<?php

namespace Idimption\Entity\FieldHook;

use Idimption\Entity\Idea;

class ProjectIdFieldHook extends BaseFieldHook
{
    use UserIdFieldHookValidateTrait;

    public function isActionSupported($isSkipped = false)
    {
        return !$isSkipped;
    }

    public function updateFieldValue()
    {
        $projectId = $this->_newFieldValue;
        if ($projectId) {
            $project = Idea::getInstance()->getRowById($projectId);
            if (!$project->isProject) {
                $updateProject = new Idea();
                $updateProject->id = $projectId;
                $updateProject->isProject = true;
                $updateProject->projectId = null;
                $updateProject->update(['isProject', 'projectId']);
            }
        }

        return false;
    }
}
