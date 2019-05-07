<?php

namespace Idimption\Entity;

class Relation extends BaseEntity
{
    use CommonTextFieldsTrait;

    /**
     * @var int
     */
    public $oppositeId;

    /**
     * @var bool
     */
    public $isDirect;

    public function __construct($data = [])
    {
        parent::__construct($data, 'relation');
    }

    public function getEntityName(User $recipient = null)
    {
        return 'relation type';
    }

    /**
     * @return self
     */
    public function getOpposite()
    {
        return $this->getRowById($this->oppositeId);
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        return $this->formatCommonTextFieldsChange($change);
    }
}
