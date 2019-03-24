<?php

namespace Idimption\Entity;

class User extends BaseEntity
{
    /**
     * @var string
     * @additionalInfoField
     */
    public $id = '';

    /**
     * @var string
     * @displayField
     */
    public $name = '';

    /**
     * @var bool
     * @readOnly
     * @hook Ignore
     */
    public $verifiedEmail = false;

    public function __construct()
    {
        parent::__construct('user');
    }

    protected function getSelectionFieldsSql()
    {
        return 'id, name, verifiedEmail';
    }
}
