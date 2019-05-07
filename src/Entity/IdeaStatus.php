<?php

namespace Idimption\Entity;

class IdeaStatus extends BaseEntity
{
    use ReferenceIdFieldTrait, CommonTextFieldsTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideastatus');
    }

    public function getEntityName(User $recipient = null)
    {
        return 'idea status';
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        return $this->formatCommonTextFieldsChange($change);
    }
}
