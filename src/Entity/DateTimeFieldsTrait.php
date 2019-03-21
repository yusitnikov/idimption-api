<?php

namespace Idimption\Entity;

trait DateTimeFieldsTrait
{
    /**
     * @var int
     * @hook CurrentTime
     * @readOnly
     */
    public $createdAt;

    /**
     * @var int
     * @hook CurrentTime
     */
    public $updatedAt;

    // TODO: automatically set on create/update
}
