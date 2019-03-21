<?php

namespace Idimption\Entity;

class EntityUpdateAction
{
    const INSERT = 'add';
    const UPDATE = 'update';
    const DELETE = 'delete';

    public static function isAllowedAction($action)
    {
        return in_array($action, [self::INSERT, self::UPDATE, self::DELETE]);
    }
}
