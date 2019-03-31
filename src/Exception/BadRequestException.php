<?php

namespace Idimption\Exception;

class BadRequestException extends HttpException
{
    public function __construct($message = 'Bad request')
    {
        parent::__construct($message, 400);
    }
}
