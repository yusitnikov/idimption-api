<?php

namespace Idimption\Entity;

use Idimption\App;

class Category extends BaseEntity implements TreeEntityInterface
{
    use ParentIdFieldTrait, ReferenceIdFieldTrait, CommonTextFieldsTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'category');
    }

    public function getEntityName(User $recipient = null)
    {
        return 'category';
    }

    public function getSummary($isHtml, $isLink = true, $excludedFieldNames = [])
    {
        $summary = parent::getSummary(false, false, $excludedFieldNames);

        if (!in_array('parentId', $excludedFieldNames)) {
            $parent = $this->getParent();
            if ($parent) {
                $summary = $parent->getSummary(false, false, $excludedFieldNames) . ' > ' . $summary;
            }
        }

        if ($isHtml) {
            $summary = htmlspecialchars($summary);

            if ($isLink) {
                $summary = '<a href="' . htmlspecialchars(App::getInstance()->getFrontEndUri('/category/' . $this->id)) . '">' . $summary . '</a>';
            }
        }

        return $summary;
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        if ($change->action === EntityUpdateAction::DELETE) {
            return '';
        }

        $text = '';
        $text .= $this->formatCommonTextFieldsChange($change);
        if ($change->action === EntityUpdateAction::UPDATE) {
            $text .= $this->formatChangeField($change, 'parentId', 'Parent');
        }
        return $text;
    }
}
