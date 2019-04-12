<?php

namespace Idimption\Entity;

use JsonSerializable;

class GuidMap implements JsonSerializable
{
    private $_map = [];

    public function add($guid, $id)
    {
        $this->_map[$guid] = $id;
    }

    public function substitute($value)
    {
        if (is_string($value)) {
            return $this->_map[$value] ?? $value;
        } elseif (is_array($value)) {
            return array_map([$this, 'substitute'], $value);
        } else {
            return $value;
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        // return null when the map is empty to prevent from formatting as a plain array
        return $this->_map ?: null;
    }
}
