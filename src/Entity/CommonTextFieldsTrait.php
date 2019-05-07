<?php

namespace Idimption\Entity;

trait CommonTextFieldsTrait
{
    /**
     * @var string
     * @displayField
     * @diffable
     */
    public $summary = '';

    /**
     * @var string
     * @additionalInfoField
     * @multiline
     * @diffable
     */
    public $description = '';

    protected function formatCommonTextFieldsChange(RowChange $change)
    {
        if ($change->action === EntityUpdateAction::DELETE) {
            return '';
        }

        $text = '';
        if ($change->action === EntityUpdateAction::UPDATE) {
            $text .= $this->formatChangeField($change, 'summary', 'Summary');
        }
        $text .= $this->formatChangeField($change, 'description', 'Description');
        return $text;
    }
}
