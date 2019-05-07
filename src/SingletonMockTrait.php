<?php

namespace Idimption;

trait SingletonMockTrait
{
    public function __construct()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::__construct();
    }

    protected function init()
    {
    }

    protected function dispose()
    {
    }
}
