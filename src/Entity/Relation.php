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

    public function __construct()
    {
        parent::__construct('relation');
    }

    /**
     * @return self
     */
    public function getOpposite()
    {
        return $this->getRowById($this->oppositeId);
    }
}
