<?php

namespace Idimption\Entity;

use JsonSerializable;

class ValueChange implements JsonSerializable
{
    /** @var mixed */
    public $fromText;

    /** @var string */
    public $fromHtml;

    /** @var mixed */
    public $toText;

    /** @var string */
    public $toHtml;

    /**
     * ValueChange constructor.
     *
     * @param mixed $fromText
     * @param string $fromHtml
     * @param mixed $toText
     * @param string $toHtml
     */
    public function __construct($fromText, $fromHtml, $toText, $toHtml)
    {
        $this->fromText = $fromText;
        $this->fromHtml = $fromHtml;
        $this->toText = $toText;
        $this->toHtml = $toHtml;
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
        return [
            'from' => $this->fromText,
            'to' => $this->toText,
        ];
    }
}
