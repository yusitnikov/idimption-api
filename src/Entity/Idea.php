<?php

namespace Idimption\Entity;

use Idimption\App;

class Idea extends BaseEntity
{
    use ReferenceIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, CommonTextFieldsTrait, StatusIdFieldTrait;

    /**
     * @var double|null
     */
    public $priority;

    public function __construct($data = [])
    {
        parent::__construct($data, 'idea');
    }

    public function getEntityName(User $recipient = null)
    {
        return ($recipient && $recipient->id === $this->userId ? 'your ' : '') . 'idea';
    }

    public function allowAnonymousCreate()
    {
        return true;
    }

    public function toArray($allFields)
    {
        $result = parent::toArray($allFields);
        $result['priority'] = $this->priority ?? $this->id;
        return $result;
    }

    public function getSummary($isHtml, $isLink = true, $excludedFieldNames = [])
    {
        $summary = parent::getSummary($isHtml, false, $excludedFieldNames);

        if ($isHtml && $isLink) {
            $summary = '<a href="' . htmlspecialchars(App::getInstance()->getFrontEndUri('/idea/' . $this->id)) . '">' . $summary . '</a>';
        }

        return $summary;
    }

    public function getUserSubscriptionReasons(User $user)
    {
        $reasons = [];

        $ideaSubscription = IdeaSubscription::getInstance()->getUserSubscriptionForObject($user, $this);
        if ($ideaSubscription === false) {
            return false;
        }
        if ($ideaSubscription) {
            $reasons[] = 'the idea';
        }

        foreach (IdeaTag::getInstance()->getRowsByIdeaId($this->id) as $ideaTag) {
            $tagSubscription = TagSubscription::getInstance()->getUserSubscriptionForObject($user, $ideaTag->getTag());
            if ($tagSubscription === false) {
                return false;
            }
            if ($tagSubscription) {
                $reasons[] = 'tag "' . $tagSubscription->getSummary(false) . '"';
            }
        }

        foreach (IdeaCategory::getInstance()->getRowsByIdeaId($this->id) as $ideaCategory) {
            $categorySubscription = CategorySubscription::getInstance()->getUserSubscriptionForObject($user, $ideaCategory->getCategory());
            if ($categorySubscription === false) {
                return false;
            }
            if ($categorySubscription) {
                $reasons[] = 'category "' . $categorySubscription->getSummary(false) . '"';
            }
        }

        $author = $this->getUser();
        if ($author) {
            $userSubscription = UserSubscription::getInstance()->getUserSubscriptionForObject($user, $author);
            if ($userSubscription === false) {
                return false;
            }
            if ($userSubscription) {
                $reasons[] = 'user ' . $userSubscription->getSummary(false);
            }
        }

        return implode(', ', $reasons);
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        if ($change->action === EntityUpdateAction::DELETE) {
            return '';
        }

        $text = '';
        $text .= $this->formatCommonTextFieldsChange($change);
        $text .= $this->formatStatusChange($change);
        $text .= $this->formatForeignTableChanges($change, IdeaTag::class, 'Tags');
        $text .= $this->formatForeignTableChanges($change, IdeaCategory::class, 'Categories');
        $text .= $this->formatForeignTableChanges($change, IdeaRelation::class, 'Relations');
        return $text;
    }

    public function getNotificationReason(RowChange $change, User $recipient)
    {
        $ideaSubscription = $this->getUserSubscriptionReasons($recipient);
        if ($ideaSubscription === false) {
            return null;
        }

        if ($recipient->subscribeToAllNewIdeas && $change->action === EntityUpdateAction::INSERT) {
            return 'you are subscribed to all new ideas';
        }

        if ($recipient->subscribeToUpdatesInMyIdeas && $this->userId === $recipient->id) {
            return 'you are subscribed to updates in your ideas';
        }

        if ($ideaSubscription) {
            return 'you are watching ' . $ideaSubscription;
        }

        return null;
    }
}
