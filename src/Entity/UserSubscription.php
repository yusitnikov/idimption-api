<?php

namespace Idimption\Entity;

class UserSubscription extends BaseEntity
{
    use UserIdFieldTrait;

    /**
     * @var string
     * @foreignClass User
     */
    public $dstUserId;

    /**
     * @var bool
     */
    public $included;

    public function __construct()
    {
        parent::__construct('usersubscription');
    }

    /**
     * @return User
     */
    public function getDstUserId()
    {
        return User::getInstance()->getRowById($this->dstUserId);
    }
}
