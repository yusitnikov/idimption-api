<?php

namespace Idimption\Entity;

class User extends BaseEntity
{
    /**
     * @var string
     * @additionalInfoField
     */
    public $id;

    /**
     * @var string
     * @displayField
     */
    public $name;

    public function __construct()
    {
        parent::__construct('user');
    }
}
