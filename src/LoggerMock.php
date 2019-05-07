<?php

namespace Idimption;

class LoggerMock extends Logger
{
    public function __construct()
    {
        parent::__construct('');
    }

    protected function init($fileName)
    {
    }

    protected function dispose()
    {
    }

    public function log($message)
    {
    }
}
