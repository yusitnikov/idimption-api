<?php

namespace Idimption\Exception;

class HttpException extends \Exception
{
    public function __construct($message = 'Internal server error', $code = 500)
    {
        parent::__construct($message, $code);
    }
}
