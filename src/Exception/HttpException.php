<?php

namespace Idimption\Exception;

use Exception;

class HttpException extends Exception
{
    // override the constructor to make arguments mandatory
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
