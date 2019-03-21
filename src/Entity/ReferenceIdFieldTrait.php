<?php

namespace Idimption\Entity;

trait ReferenceIdFieldTrait
{
    /**
     * @var string|null
     */
    public $referenceId;

    public function getAllRowsByReferenceId()
    {
        return $this->getRowsMap(['referenceId']);
    }

    /**
     * @param string $referenceId
     * @return static|null
     */
    public function getRowByReferenceId($referenceId)
    {
        return $this->getAllRowsByReferenceId()[$referenceId] ?? null;
    }
}
