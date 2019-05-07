<?php

namespace Idimption\Entity;

class TagSubscription extends BaseEntity
{
    use SubscriptionTrait, TagIdFieldTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'tagsubscription');
    }
}
