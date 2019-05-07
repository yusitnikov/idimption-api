<?php

namespace Idimption\Entity;

class UserSubscription extends BaseEntity
{
    use SubscriptionTrait;

    /**
     * @var int
     * @foreignClass User
     * @displayField
     */
    public $dstUserId;

    public function __construct($data = [])
    {
        parent::__construct($data, 'usersubscription');
    }

    /**
     * @return User
     */
    public function getDstUserId()
    {
        return User::getInstance()->getRowById($this->dstUserId);
    }
}
