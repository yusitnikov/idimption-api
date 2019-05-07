<?php

namespace Idimption\Entity;

class CategorySubscription extends BaseEntity
{
    use SubscriptionTrait, CategoryIdFieldTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'categorysubscription');
    }
}
