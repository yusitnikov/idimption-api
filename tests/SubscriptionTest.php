<?php

namespace Idimption\Test;

use Idimption\Auth;
use Idimption\DbMock;
use Idimption\Entity\Category;
use Idimption\Entity\Idea;
use Idimption\Entity\IdeaRelation;
use Idimption\Entity\User;
use Idimption\Html;

abstract class SubscriptionTest extends BaseTest
{
    const PERFORMER_USER_ID = '1';
    const PERFORMER_USER_EMAIL = 'performer@mail.com';

    const SUBSCRIBER_USER_ID = '2';
    const SUBSCRIBER_USER_EMAIL = 'subscriber@mail.com';

    const CATEGORY_EUROPE_ID = '1';
    const CATEGORY_ENGLAND_ID = '2';
    const CATEGORY_LONDON_ID = '3';
    const CATEGORY_LIVERPOOL_ID = '4';
    const CATEGORY_GERMANY_ID = '5';
    const CATEGORY_BERLIN_ID = '6';
    const CATEGORY_ASIA_ID = '7';
    const CATEGORY_JAPAN_ID = '8';
    const CATEGORY_TOKYO_ID = '9';

    const RELATION_IDEA_ID1 = '1';
    const RELATION_IDEA_ID2 = '2';

    const DB_AUTO_INCREMENT_ID = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_dbMock->addMockRows([
            new User([
                'id' => self::PERFORMER_USER_ID,
                'email' => self::PERFORMER_USER_EMAIL,
                'name' => 'Performer',
                'verifiedEmail' => true,
                'isAdmin' => true,
                'subscribeToAll' => true,
            ]),
            new Idea([
                'id' => self::RELATION_IDEA_ID1,
                'summary' => 'Rely on me 1',
                'statusId' => DbMock::STATUS_PLANNED_ID,
            ]),
            new Idea([
                'id' => self::RELATION_IDEA_ID2,
                'summary' => 'Rely on me 2',
                'statusId' => DbMock::STATUS_DONE_ID,
            ]),
            new IdeaRelation([
                'id' => '1',
                'ideaId' => self::RELATION_IDEA_ID1,
                'relationId' => DbMock::RELATION_IMPLEMENTS_ID,
                'dstIdeaId' => self::RELATION_IDEA_ID2,
            ]),
            new IdeaRelation([
                'id' => '-1',
                'ideaId' => self::RELATION_IDEA_ID2,
                'relationId' => DbMock::RELATION_IMPLEMENTED_BY_ID,
                'dstIdeaId' => self::RELATION_IDEA_ID1,
            ]),
            new Category([
                'id' => self::CATEGORY_EUROPE_ID,
                'summary' => 'Europe',
            ]),
            new Category([
                'id' => self::CATEGORY_ENGLAND_ID,
                'summary' => 'England',
                'parentId' => self::CATEGORY_EUROPE_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_LONDON_ID,
                'summary' => 'London',
                'parentId' => self::CATEGORY_ENGLAND_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_LIVERPOOL_ID,
                'summary' => 'Liverpool',
                'parentId' => self::CATEGORY_ENGLAND_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_GERMANY_ID,
                'summary' => 'Germany',
                'parentId' => self::CATEGORY_EUROPE_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_BERLIN_ID,
                'summary' => 'Berlin',
                'parentId' => self::CATEGORY_GERMANY_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_ASIA_ID,
                'summary' => 'Asia',
            ]),
            new Category([
                'id' => self::CATEGORY_JAPAN_ID,
                'summary' => 'Japan',
                'parentId' => self::CATEGORY_ASIA_ID,
            ]),
            new Category([
                'id' => self::CATEGORY_TOKYO_ID,
                'summary' => 'Tokyo',
                'parentId' => self::CATEGORY_JAPAN_ID,
            ]),
        ]);

        $this->_dbMock->autoIncrementId = self::DB_AUTO_INCREMENT_ID;
    }

    protected function login()
    {
        Auth::setLoggedInUserId(self::PERFORMER_USER_ID);
    }

    protected function addSubscriberUser($configs = [])
    {
        $this->_dbMock->addMockRows([
            new User(array_merge([
                'id' => self::SUBSCRIBER_USER_ID,
                'email' => self::SUBSCRIBER_USER_EMAIL,
                'name' => 'Subscriber',
                'verifiedEmail' => true,
                'isAdmin' => false,
                'subscribeToAll' => false,
                'subscribeToAllNewIdeas' => false,
                'subscribeToUpdatesInMyIdeas' => false,
                'subscribeToCommentsOnMyIdeas' => false,
                'subscribeToReplyComments' => false,
                'subscribeToMentionComments' => false,
                'subscribeToVotesInMyIdeas' => false,
                'subscribeToWatchesInMyIdeas' => false,
                'subscribeToUnwatchesInMyIdeas' => false,
            ], $configs)),
        ]);
    }

    protected function resetUsers()
    {
        $this->_dbMock->allEntities['user'] = [];
    }

    protected function buildExpectedNotification($expectedSubject, $expectedReason, $expectedContent = [])
    {
        if (is_array($expectedSubject)) {
            list($expectedSubjectText, $expectedSubjectHtml) = $expectedSubject;
        } else {
            $expectedSubjectText = $expectedSubject;
            $expectedSubjectHtml = htmlspecialchars($expectedSubject);
        }
        $verificationCode = User::getInstance()->getRowById(self::SUBSCRIBER_USER_ID)->getVerificationCode();
        $expectedLink = "<a href='http://localhost:8080/auth/verify/$verificationCode?r=/profile'>Manage subscription settings</a>";

        $expectedContent = (array)$expectedContent;
        $expectedContent = array_map([Html::class, 'section'], $expectedContent);
        $expectedContent = array_merge(
            [
                "<h2>" . $expectedSubjectHtml . "</h2>",
            ],
            $expectedContent,
            [
                "<p>You received this email because $expectedReason. $expectedLink.</p>",
                self::EXPECTED_EMAIL_FOOTER,
            ]
        );
        return [
            'subject' => $expectedSubjectText,
            'content' => implode("\n", $expectedContent),
            'to' => [self::SUBSCRIBER_USER_EMAIL],
            'cc' => [],
            'bcc' => [],
            'queue' => true,
        ];
    }
}
