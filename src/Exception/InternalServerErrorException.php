<?php

namespace Idimption\Exception;

class InternalServerErrorException extends HttpException
{
    public function __construct($message = 'Internal server error')
    {
        parent::__construct($message, 500);
    }
}
