<?php

namespace Idimption\Entity;

class IdeaComment extends BaseEntity implements TreeEntityInterface
{
    use FieldsOrderTrait;
    use ParentIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, IdeaIdFieldTrait;

    /**
     * @var string
     * @displayField
     * @multiline
     * @diffable
     */
    public $message = '';

    public function __construct($data = [])
    {
        parent::__construct($data, 'ideacomment');
    }

    public function getEntityName(User $recipient = null)
    {
        return ($recipient && $recipient->id === $this->userId ? 'your ' : '') . 'comment';
    }

    protected function getFieldsOrder()
    {
        return ['ideaId'];
    }

    public function isUserMentioned(User $user)
    {
        $map = IdeaCommentMention::getInstance()->getRowsMap(['userId', 'ideaCommentId']);
        return isset($map[$user->id][$this->id]);
    }

    protected function getSummarySeparator()
    {
        return ' - ';
    }

    public function getChangeSummary(RowChange $change, User $recipient, $isHtml)
    {
        return EntityUpdateAction::getActionName($change->action) . ' ' . $this->getEntityName($recipient) . ' on ' . $change->getInfoRow()->formatFieldValue('ideaId', $isHtml, $recipient);
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        return $this->formatForeignTableChanges($change, IdeaCommentMention::class, 'Recipients')
             . $this->formatChangeField($change, 'message', 'Message', true);
    }

    public function getNotificationReason(RowChange $change, User $recipient)
    {
        $idea = $this->getIdea();
        $ideaSubscription = $idea->getUserSubscriptionReasons($recipient);
        if ($ideaSubscription === false) {
            return null;
        }

        if ($recipient->subscribeToMentionComments && $this->isUserMentioned($recipient)) {
            return 'you are mentioned directly';
        }

        if ($recipient->subscribeToCommentsOnMyIdeas && $idea->userId === $recipient->id) {
            return 'you are subscribed to comments to your ideas';
        }

        if ($ideaSubscription) {
            return 'you are subscribed to comments to ideas you\'re watching';
        }

        if ($recipient->subscribeToReplyComments) {
            for ($parent = $this->getParent(); $parent; $parent = $parent->getParent()) {
                if ($parent->userId === $recipient->id) {
                    return 'you are subscribed to replies to your comments';
                }
            }
        }

        return null;
    }
}
