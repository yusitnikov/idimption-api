<?php

namespace Idimption\Entity;

trait UserIdFieldTrait
{
    /**
     * @var string|null
     * @foreignClass User
     * @hook UserId
     * @readOnly
     */
    public $userId;

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->userId ? User::getInstance()->getRowById($this->userId) : null;
    }
}
