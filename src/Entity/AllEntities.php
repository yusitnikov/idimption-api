<?php

namespace Idimption\Entity;

use Idimption\Auth;
use Idimption\Db;
use Idimption\Exception\BadRequestException;
use ReflectionClass;

class AllEntities
{
    /**
     * @return BaseEntity[]
     */
    public static function getAllModels()
    {
        static $models = [];

        if (!$models) {
            $dir = opendir(__DIR__);
            while (($fileName = readdir($dir)) !== false) {
                if (substr($fileName, -4) === '.php') {
                    /** @var BaseEntity|string $className */
                    $className = __NAMESPACE__ . '\\' . substr($fileName, 0, -4);
                    $reflection = new ReflectionClass($className);
                    if (!$reflection->isAbstract() && $reflection->isSubclassOf(BaseEntity::class)) {
                        $instance = $className::getInstance();
                        $models[$instance->getTableName()] = $instance;
                    }
                }
            }
            closedir($dir);
        }

        return $models;
    }

    /**
     * @param string $tableName
     * @return BaseEntity|null
     */
    public static function getModelByTableName($tableName)
    {
        return self::getAllModels()[$tableName] ?? null;
    }

    public static function getSchema()
    {
        $schemaMap = array_map(
            function(BaseEntity $model) {
                return $model->getSchema();
            },
            self::getAllModels()
        );
        foreach ($schemaMap as $tableName => $schema) {
            foreach ($schema['fieldsInfo'] as $fieldName => $fieldInfo) {
                if (isset($fieldInfo['foreignTable'])) {
                    $foreignTable = $fieldInfo['foreignTable'];
                    $schemaMap[$foreignTable]['foreignKeys'][$tableName] = $fieldName;
                }
            }
        }
        return $schemaMap;
    }

    public static function getAllRows()
    {
        return array_map(
            function(BaseEntity $model) {
                return $model->getAllRows();
            },
            self::getAllModels()
        );
    }

    public static function save($transitions)
    {
        $guidMap = new GuidMap();

        Db::transaction(function() use($transitions, $guidMap) {
            foreach ($transitions as $index => $transition) {
                $action = $transition['type'] ?? null;
                $tableName = $transition['tableName'] ?? null;
                $row = $transition['row'] ?? null;

                if (!$action) {
                    throw new BadRequestException('Missing type parameter in transaction #' . $index);
                }
                if (!EntityUpdateAction::isAllowedAction($action)) {
                    throw new BadRequestException('Unrecognized type in transaction #' . $index . ': ' . $action);
                }
                if (!$tableName) {
                    throw new BadRequestException('Missing tableName parameter in transaction #' . $index);
                }
                if (!is_array($row)) {
                    throw new BadRequestException('Missing row parameter in transaction #' . $index);
                }

                $row = $guidMap->substitute($row);

                $model = self::getModelByTableName($tableName);
                if (!$model) {
                    throw new BadRequestException('Unrecognized tableName in transaction #' . $index . ': ' . $tableName);
                }
                if (!Auth::getLoggedInUser()) {
                    if ($action !== EntityUpdateAction::INSERT || !$model->allowAnonymousCreate()) {
                        throw new BadRequestException('Anonymous access not allowed');
                    }
                }
                $rowObject = $model::fromJson($row);
                $rowObject->setGuidMap($guidMap);
                $rowObject->save($action, false, array_keys($row));
            }
        });

        self::clearCache();

        return $guidMap;
    }

    public static function clearCache()
    {
        foreach (self::getAllModels() as $model) {
            $model->clearCache();
        }
    }
}
