<?php

namespace Idimption;

class AppMock extends App
{
    use SingletonMockTrait;

    protected function init()
    {
        $this->_config = require(__DIR__ . '/../config/config.example.php');
        $this->_sessionId = 1;
        $this->_startTime = time();
        $this->_params = [];
        $this->_log = new LoggerMock();
    }
}
