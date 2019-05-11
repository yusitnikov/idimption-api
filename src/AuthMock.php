<?php

namespace Idimption;

class AuthMock extends Auth
{
    use SingletonMockTrait;

    protected function init()
    {
        $this->_sessionId = 0;
        $this->_sessionData = [];
    }
}
