<?php

namespace Idimption\Entity;

trait FieldsOrderTrait
{
    protected abstract function getFieldsOrder();

    public function getAllFields()
    {
        /** @noinspection PhpUndefinedClassInspection */
        $allFields = parent::getAllFields();
        $sortedFields = $this->getFieldsOrder();
        return array_merge(
            $sortedFields,
            array_diff(
                $allFields,
                $sortedFields
            )
        );
    }
}
