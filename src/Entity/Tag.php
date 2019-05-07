<?php

namespace Idimption\Entity;

class Tag extends BaseEntity
{
    use CommonTextFieldsTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'tag');
    }

    public function getEntityName(User $recipient = null)
    {
        return 'tag';
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        return $this->formatCommonTextFieldsChange($change);
    }
}
