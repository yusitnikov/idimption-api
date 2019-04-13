<?php

namespace Idimption\Entity;

use Idimption\App;

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

    public function isJustCreated()
    {
        return (int)$this->createdAt === App::getInstance()->getStartTime();
    }

    public function isJustUpdated()
    {
        return (int)$this->updatedAt === App::getInstance()->getStartTime();
    }
}
