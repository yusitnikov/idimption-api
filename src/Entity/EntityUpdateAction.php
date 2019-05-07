<?php

namespace Idimption\Entity;

use Idimption\Exception\InternalServerErrorException;

class EntityUpdateAction
{
    const INSERT = 'add';
    const UPDATE = 'update';
    const DELETE = 'delete';

    public static function isAllowedAction($action)
    {
        return in_array($action, [self::INSERT, self::UPDATE, self::DELETE]);
    }

    public static function getActionName($action)
    {
        switch ($action) {
            case EntityUpdateAction::INSERT:
                return 'added';
            case EntityUpdateAction::UPDATE:
                return 'updated';
            case EntityUpdateAction::DELETE:
                return 'removed';
            default:
                throw new InternalServerErrorException();
        }
    }
}
