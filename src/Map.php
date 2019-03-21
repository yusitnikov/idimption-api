<?php

namespace Idimption;

use Idimption\Entity\BaseEntity;

class Map
{
    /**
     * @param array|BaseEntity $row
     * @param string $fieldName
     * @return mixed
     */
    private static function _getFieldValue($row, $fieldName)
    {
        return is_array($row) ? $row[$fieldName] : $row->$fieldName;
    }

    /**
     * @param array[]|BaseEntity[] $rows
     * @param string[] $keyFields
     * @param string|null $resultField
     * @param bool $multiple
     * @return array
     */
    public static function map($rows, $keyFields = ['id'], $resultField = null, $multiple = false)
    {
        $map = [];
        foreach ($rows as $row) {
            $mapRef =& $map;
            foreach ($keyFields as $keyField) {
                $value = self::_getFieldValue($row, $keyField);
                if ($value === null) {
                    continue 2;
                }
                $mapRef =& $mapRef[$value];
            }
            $value = $resultField ? self::_getFieldValue($row, $resultField) : $row;
            if ($multiple) {
                $mapRef[] = $value;
            } else {
                $mapRef = $value;
            }
        }
        return $map;
    }
}
