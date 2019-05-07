<?php

namespace Idimption\Entity;

trait SubscriptionTrait
{
    use RelationEntityTrait, FieldsOrderTrait;
    use UserIdLinkFieldTrait;

    /**
     * @var bool|null
     * @displayField
     * @format "not subscribed to": null, "subscribed to": true, "ignores": false
     */
    public $included;

    protected function getFieldsOrder()
    {
        return ['id', 'userId', 'included'];
    }

    protected function getDestinationField()
    {
        return $this->getVisibleFields()[3];
    }

    /**
     * @param User $user
     * @param BaseEntity $object
     * @return BaseEntity|false|null
     */
    public function getUserSubscriptionForObject(User $user, BaseEntity $object)
    {
        /** @var static $subscription */
        $subscription = $this->getRowsMap(['userId', $this->getDestinationField()])[$user->id][$object->id] ?? null;
        if ($subscription) {
            return $subscription->included ? $object : $subscription->included;
        }
        if ($object instanceof TreeEntityInterface) {
            /** @var BaseEntity|null $parentObject */
            $parentObject = $object->getParent();
            if ($parentObject) {
                return $this->getUserSubscriptionForObject($user, $parentObject);
            }
        }
        return null;
    }

    public function getChangeSummary(RowChange $change, User $recipient, $isHtml)
    {
        return $change->getUpdatedRow()->formatFieldValue('included', $isHtml, $recipient) . ' ' . $change->getInfoRow()->formatFieldValue($this->getDestinationField(), $isHtml, $recipient);
    }

    public function formatChange(
        /** @noinspection PhpUnusedParameterInspection */
        RowChange $change, User $recipient
    )
    {
        return ' ';
    }
}
