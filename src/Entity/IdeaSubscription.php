<?php

namespace Idimption\Entity;

class IdeaSubscription extends BaseEntity
{
    use SubscriptionTrait, IdeaIdFieldTrait;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideasubscription');
    }

    public function allowAnonymousCreate()
    {
        return false;
    }

    public function getNotificationReason(RowChange $change, User $recipient)
    {
        if ($this->getIdea()->userId === $recipient->id) {
            /** @var static $updatedRow */
            $updatedRow = $change->getUpdatedRow();
            if ($recipient->subscribeToWatchesInMyIdeas && $updatedRow->included === true) {
                return 'you are subscribed to subscriptions to your ideas';
            }

            /** @var static $originalRow */
            $originalRow = $change->getOriginalRow();
            if ($recipient->subscribeToUnwatchesInMyIdeas && $originalRow->included === true) {
                return 'you are subscribed to un-subscriptions from your ideas';
            }
        }

        return null;
    }
}
