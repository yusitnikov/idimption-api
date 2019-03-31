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

    public function allowAnonymousCreate()
    {
        return true;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->userId ? User::getInstance()->getRowById($this->userId) : null;
    }
}
