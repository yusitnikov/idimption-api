<?php

namespace Idimption\Entity;

trait UserIdLinkFieldTrait
{
    use UserIdFieldTrait;

    /**
     * @var string
     * @foreignClass User
     * @displayField
     * @readOnly
     */
    public $userId;
}
