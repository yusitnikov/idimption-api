<?php

namespace Idimption\Entity;

class IdeaVote extends BaseEntity
{
    use FieldsOrderTrait;
    use IdeaIdFieldTrait, UserIdLinkFieldTrait;

    /**
     * @var bool
     * @displayField
     * @format "voted for": true, "voted against": false, "removed the vote for": null
     */
    public $isPositive;

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideavote');
    }

    public function allowAnonymousCreate()
    {
        return false;
    }

    protected function getFieldsOrder()
    {
        return ['userId', 'isPositive'];
    }

    public function getChangeSummary(RowChange $change, User $recipient, $isHtml)
    {
        return $change->getUpdatedRow()->formatFieldValue('isPositive', $isHtml, $recipient) . ' ' . $change->getInfoRow()->formatFieldValue('ideaId', $isHtml, $recipient);
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        return ' ';
    }

    public function getNotificationReason(RowChange $change, User $recipient)
    {
        if ($recipient->subscribeToVotesInMyIdeas && $this->getIdea()->userId === $recipient->id) {
            return 'you are subscribed to votes to your ideas';
        }

        return null;
    }
}
