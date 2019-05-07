<?php

namespace Idimption;

trait SingletonWithMockTrait
{
    protected static $_instance;

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$_instance = self::$_instance ?? new self();
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    protected function __construct()
    {
        $this->init();
    }

    function __destruct()
    {
        $this->dispose();
    }

    protected function init()
    {
    }

    protected function dispose()
    {
    }
}
