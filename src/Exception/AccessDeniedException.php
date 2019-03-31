<?php

namespace Idimption\Exception;

class AccessDeniedException extends HttpException
{
    public function __construct($message = 'Access denied')
    {
        parent::__construct($message, 403);
    }
}
