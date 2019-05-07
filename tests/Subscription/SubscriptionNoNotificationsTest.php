<?php

namespace Idimption\Test\Subscription;

use Idimption\DbMock;
use Idimption\Entity\AllEntities;
use Idimption\Test\SubscriptionTest;
use PHPUnit\Framework\Assert;

class SubscriptionNoNotificationsTest extends SubscriptionTest
{
    public function testNoNotificationsToPerformer()
    {
        $this->login();
        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Idea 1',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals([], $this->_emailMock->popEmails());
    }

    public function testNoNotificationsToNotVerifiedEmail()
    {
        $this->addSubscriberUser([
            'verifiedEmail' => false,
            'subscribeToAll' => true,
        ]);

        $this->login();
        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Idea 1',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals([], $this->_emailMock->popEmails());
    }
}
