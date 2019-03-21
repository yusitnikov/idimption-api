<?php

namespace Idimption\Entity;

class Tag extends BaseEntity
{
    use CommonTextFieldsTrait;

    public function __construct()
    {
        parent::__construct('tag');
    }
}
