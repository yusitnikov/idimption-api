<?php

namespace Idimption\Entity;

use JsonSerializable;

class RowChange implements JsonSerializable
{
    /** @var EntityUpdateAction|string */
    public $action;

    /** @var BaseEntity|null */
    public $originalRow;

    /** @var BaseEntity|null */
    public $updateRow;

    /** @var string[] */
    public $changedFields = [];

    /** @var RowChange[][] */
    public $foreignRowChanges = [];

    /**
     * RowChange constructor.
     *
     * @param EntityUpdateAction|string $action
     * @param BaseEntity|null $originalRow
     * @param BaseEntity|null $updateRow
     * @param string[] $changedFields
     */
    public function __construct($action, $originalRow, $updateRow, $changedFields = [])
    {
        $this->action = $action;
        $this->originalRow = $originalRow;
        $this->updateRow = $updateRow;
        $this->changedFields = $changedFields;
    }

    /**
     * @param string $tableName
     * @param RowChange $change
     */
    public function addForeignRowChange($tableName, $change)
    {
        $this->foreignRowChanges[$tableName][] = $change;
    }

    /**
     * @return BaseEntity
     */
    public function getOriginalRow()
    {
        if ($this->originalRow) {
            return $this->originalRow;
        } else {
            /** @var BaseEntity|string $className */
            $className = get_class($this->updateRow);
            /** @var BaseEntity $row */
            $row = new $className();
            foreach ($row as $fieldName => $value) {
                $row->$fieldName = null;
            }
            return $row;
        }
    }

    /**
     * @return BaseEntity
     */
    public function getUpdatedRow()
    {
        switch ($this->action) {
            case EntityUpdateAction::INSERT:
                return $this->updateRow;
            case EntityUpdateAction::UPDATE:
                $row = clone $this->originalRow;
                foreach ($row->getVisibleFields() as $fieldName) {
                    if (in_array($fieldName, $this->changedFields)) {
                        $row->$fieldName = $this->updateRow->$fieldName;
                    }
                }
                return $row;
            case EntityUpdateAction::DELETE:
            default:
                /** @var BaseEntity|string $className */
                $className = get_class($this->originalRow);
                return new $className();
        }
    }

    /**
     * @return BaseEntity
     */
    public function getInfoRow()
    {
        return $this->action === EntityUpdateAction::DELETE ? $this->originalRow : $this->getUpdatedRow();
    }

    /**
     * @param string $fieldName
     * @param bool $force
     * @return ValueChange|null
     */
    public function getFieldChange($fieldName, $force = false)
    {
        if ($force || $this->action !== EntityUpdateAction::UPDATE || in_array($fieldName, $this->changedFields)) {
            $originalRow = $this->getOriginalRow();
            $originalText = $originalRow->formatFieldValue($fieldName, false, null, false);
            $originalHtml = $originalRow->formatFieldValue($fieldName, true, null, false);

            $newRow = $this->getUpdatedRow();
            $newText = $newRow->formatFieldValue($fieldName, false, null, false);
            $newHtml = $newRow->formatFieldValue($fieldName, true, null, false);

            if ($force || $newText !== $originalText) {
                return new ValueChange($originalText, $originalHtml, $newText, $newHtml);
            }
        }

        return null;
    }

    /**
     * @param bool $force
     * @return ValueChange[]
     */
    public function getFieldChanges($force = false)
    {
        $changes = [];

        foreach ($this->getInfoRow()->getVisibleFields() as $fieldName) {
            if ($fieldName !== 'id') {
                $fieldChange = $this->getFieldChange($fieldName, $force);
                if ($fieldChange) {
                    $changes[$fieldName] = $fieldChange;
                }
            }
        }

        return $changes;
    }

    private function format($includeParentSummary = true)
    {
        $row = $this->getInfoRow();
        $result = [
            'action' => $this->action,
            'summary' => $includeParentSummary ? $row->getSummary(false) : $row->getSummaryWithoutParent(false),
            'fieldChanges' => $this->getFieldChanges(),
        ];
        if ($this->action !== EntityUpdateAction::DELETE) {
            $result['foreignRowChanges'] = array_map(function($changes) {
                return array_map(function(RowChange $change) {
                    return $change->format(false);
                }, $changes);
            }, $this->foreignRowChanges);
        }
        return $result;
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
        return $this->format();
    }
}
