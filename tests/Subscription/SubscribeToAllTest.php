<?php

namespace Idimption\Test\Subscription;

use Idimption\DbMock;
use Idimption\Entity\AllEntities;
use Idimption\Entity\Idea;
use Idimption\Entity\IdeaVote;
use Idimption\Html;
use Idimption\Test\SubscriptionTest;
use PHPUnit\Framework\Assert;

/**
 * Class SubscribeToAllTest
 *
 * Tests that user with enabled "subscribe to all updates" receives all notifications in the correct format
 *
 * @package Idimption\Test\Subscription
 */
class SubscribeToAllTest extends SubscriptionTest
{
    const EXPECTED_REASON = 'you are subscribed to all updates';

    // region Helper methods

    protected function addSubscriberUser($configs = [])
    {
        $configs['subscribeToAll'] = true;
        parent::addSubscriberUser($configs);
    }

    protected function toggleLogin($isLoggedIn)
    {
        if ($isLoggedIn) {
            $this->login();
        } else {
            $this->resetUsers();
        }
    }

    public function getIsLoggedInData()
    {
        return [
            'logged in' => [true],
            'not logged in' => [false],
        ];
    }

    // endregion

    // region Idea, IdeaTag, IdeaCategory, IdeaRelation

    /**
     * @dataProvider getIsLoggedInData
     * @param bool $isLoggedIn
     */
    public function testSimpleIdea($isLoggedIn)
    {
        $this->toggleLogin($isLoggedIn);
        $this->addSubscriberUser();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Test Idea Summary',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
        ]);
        $ideaId = $guids->substitute('fakeIdeaId');
        $this->_appMock->sendNotifications();
        $expectedPerformer = $isLoggedIn ? 'Performer' : 'Guest';
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added idea Test Idea Summary',
                        $expectedPerformer . ' added idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Status:') . ' Needs review'
                ),
            ],
            $this->_emailMock->popEmails()
        );

        if (!$isLoggedIn) {
            // guests can't update/delete anything, so skip
            return;
        }

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'idea',
                'row' => [
                    'id' => $ideaId,
                    'summary' => 'Test Idea New Summary',
                    'statusId' => DbMock::STATUS_DONE_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Test Idea New Summary',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea New Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Summary:') . ' Test Idea ' . Html::diffAdd('New ') . 'Summary',
                        Html::bold('Status:') . ' ' . Html::diffDelete('Needs review') . Html::diffAdd('Done'),
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'delete',
                'tableName' => 'idea',
                'row' => ['id' => $ideaId],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer removed idea Test Idea New Summary',
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    public function testComplexIdea()
    {
        $this->addSubscriberUser();

        $this->login();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Test Idea Summary',
                    'description' => "Test Idea\nDescription",
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideatag',
                'row' => [
                    'id' => 'fakeIdeaTagId1',
                    'ideaId' => 'fakeIdeaId',
                    'tagId' => DbMock::TAG_PROJECT_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideatag',
                'row' => [
                    'id' => 'fakeIdeaTagId2',
                    'ideaId' => 'fakeIdeaId',
                    'tagId' => DbMock::TAG_ENHANCEMENT_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'idearelation',
                'row' => [
                    'id' => 'fakeIdeaRelationId1',
                    'ideaId' => 'fakeIdeaId',
                    'relationId' => DbMock::RELATION_IMPLEMENTS_ID,
                    'dstIdeaId' => self::RELATION_IDEA_ID1,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'idearelation',
                'row' => [
                    'id' => 'fakeIdeaRelationId2',
                    'ideaId' => 'fakeIdeaId',
                    'relationId' => DbMock::RELATION_RELATES_TO_ID,
                    'dstIdeaId' => self::RELATION_IDEA_ID2,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideacategory',
                'row' => [
                    'id' => 'fakeIdeaCategoryId1',
                    'ideaId' => 'fakeIdeaId',
                    'categoryId' => self::CATEGORY_BERLIN_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideacategory',
                'row' => [
                    'id' => 'fakeIdeaCategoryId2',
                    'ideaId' => 'fakeIdeaId',
                    'categoryId' => self::CATEGORY_TOKYO_ID,
                ],
            ],
        ]);
        $ideaId = $guids->substitute('fakeIdeaId');
        $ideaTagId2 = $guids->substitute('fakeIdeaTagId2');
        $ideaCategoryId2 = $guids->substitute('fakeIdeaCategoryId2');
        $ideaRelationId1 = $guids->substitute('fakeIdeaRelationId1');
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer added idea Test Idea Summary',
                        'Performer added idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Description:') . ' ' . Html::multiline("Test Idea\nDescription"),
                        Html::bold('Status:') . ' Needs review',
                        Html::bold('Tags:') . ' project, enhancement',
                        Html::bold('Categories:') . ' <a href="http://localhost:8080/category/' . self::CATEGORY_BERLIN_ID . '">Europe &gt; Germany &gt; Berlin</a>, <a href="http://localhost:8080/category/' . self::CATEGORY_TOKYO_ID . '">Asia &gt; Japan &gt; Tokyo</a>',
                        Html::bold('Relations:') . ' implements <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>, relates to <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>',
                    ]
                ),
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Rely on me 1',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Relations:') . ' implements <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>, ' . Html::diffAdd('implemented by <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>')
                ),
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Rely on me 2',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Relations:') . ' implemented by <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>, ' . Html::diffAdd('relates to <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'idea',
                'row' => [
                    'id' => $ideaId,
                    'description' => "Idea\nDescription",
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Test Idea Summary',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Description:') . ' ' . Html::multiline(Html::diffDelete('Test ') . "Idea\nDescription")
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'delete',
                'tableName' => 'ideatag',
                'row' => [
                    'id' => $ideaTagId2,
                ],
            ],
            [
                'type' => 'delete',
                'tableName' => 'idearelation',
                'row' => [
                    'id' => $ideaRelationId1,
                ],
            ],
            [
                'type' => 'delete',
                'tableName' => 'ideacategory',
                'row' => [
                    'id' => $ideaCategoryId2,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Test Idea Summary',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Tags:') . ' project, ' . Html::diffDelete('enhancement'),
                        Html::bold('Categories:') . ' <a href="http://localhost:8080/category/' . self::CATEGORY_BERLIN_ID . '">Europe &gt; Germany &gt; Berlin</a>, ' . Html::diffDelete('<a href="http://localhost:8080/category/' . self::CATEGORY_TOKYO_ID . '">Asia &gt; Japan &gt; Tokyo</a>'),
                        Html::bold('Relations:') . ' relates to <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>, ' . Html::diffDelete('implements <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>'),
                    ]
                ),
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Rely on me 1',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Relations:') . ' implements <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>, ' . Html::diffDelete('implemented by <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'idea',
                'row' => [
                    'id' => $ideaId,
                    'description' => '',
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideatag',
                'row' => [
                    'id' => 'fakeIdeaTagId3',
                    'ideaId' => $ideaId,
                    'tagId' => DbMock::TAG_FEATURE_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'idearelation',
                'row' => [
                    'id' => 'fakeIdeaRelationId3',
                    'ideaId' => $ideaId,
                    'relationId' => DbMock::RELATION_IMPLEMENTED_BY_ID,
                    'dstIdeaId' => self::RELATION_IDEA_ID2,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideacategory',
                'row' => [
                    'id' => 'fakeIdeaCategoryId3',
                    'ideaId' => $ideaId,
                    'categoryId' => self::CATEGORY_LONDON_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Test Idea Summary',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Description:') . ' ' . Html::multiline(Html::diffDelete("Idea\nDescription")),
                        Html::bold('Tags:') . ' project, ' . Html::diffAdd('feature'),
                        Html::bold('Categories:') . ' <a href="http://localhost:8080/category/' . self::CATEGORY_BERLIN_ID . '">Europe &gt; Germany &gt; Berlin</a>, ' . Html::diffAdd('<a href="http://localhost:8080/category/' . self::CATEGORY_LONDON_ID . '">Europe &gt; England &gt; London</a>'),
                        Html::bold('Relations:') . ' relates to <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>, ' . Html::diffAdd('implemented by <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>'),
                    ]
                ),
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Rely on me 2',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID2 . '">Rely on me 2</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Relations:') . ' implemented by <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>, relates to <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>, ' . Html::diffAdd('implements <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'delete',
                'tableName' => 'idea',
                'row' => ['id' => $ideaId],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer removed idea Test Idea Summary',
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    /**
     * @dataProvider getIsLoggedInData
     * @param bool $isLoggedIn
     */
    public function testAddAsProject($isLoggedIn)
    {
        $this->toggleLogin($isLoggedIn);
        $this->addSubscriberUser();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeProjectId',
                    'summary' => 'Test Project',
                    'isProject' => true,
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
        ]);
        $ideaId = $guids->substitute('fakeProjectId');
        $this->_appMock->sendNotifications();
        $expectedPerformer = $isLoggedIn ? 'Performer' : 'Guest';
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added project Test Project',
                        $expectedPerformer . ' added project <a href="http://localhost:8080/idea/' . $ideaId . '">Test Project</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Status:') . ' Needs review',
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    public function testProject()
    {
        $this->addSubscriberUser();
        $this->login();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Test Idea Summary',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                    'projectId' => self::RELATION_IDEA_ID1,
                ],
            ],
        ]);
        $ideaId = $guids->substitute('fakeIdeaId');
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated project Rely on me 1',
                        'Performer updated project <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Type:') . ' ' . Html::diffDelete('idea') . Html::diffAdd('project')
                ),
                $this->buildExpectedNotification(
                    [
                        'Performer added idea Test Idea Summary',
                        'Performer added idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Status:') . ' Needs review',
                        Html::bold('Project:') . ' <a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>',
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'idea',
                'row' => [
                    'id' => $ideaId,
                    'projectId' => null,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated idea Test Idea Summary',
                        'Performer updated idea <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Project:') . ' ' . Html::diffDelete('<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'idea',
                'row' => [
                    'id' => $ideaId,
                    'isProject' => true,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated project Test Idea Summary',
                        'Performer updated project <a href="http://localhost:8080/idea/' . $ideaId . '">Test Idea Summary</a>',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Type:') . ' ' . Html::diffDelete('idea') . Html::diffAdd('project')
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region IdeaComment, IdeaCommentMention

    /**
     * @dataProvider getIsLoggedInData
     * @param bool $isLoggedIn
     */
    public function testAddSimpleComment($isLoggedIn)
    {
        $this->toggleLogin($isLoggedIn);
        $this->addSubscriberUser();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'ideacomment',
                'row' => [
                    'id' => 'fakeCommentId',
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'message' => 'Test Comment',
                ],
            ],
        ]);
        $commentId = $guids->substitute('fakeCommentId');
        $this->_appMock->sendNotifications();
        $expectedPerformer = $isLoggedIn ? 'Performer' : 'Guest';
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added comment on idea "Rely on me 1"',
                        $expectedPerformer . ' added comment on idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Message:') . ' ' . Html::multiline('Test Comment')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'ideacomment',
                'row' => [
                    'id' => 'fakeReplyId',
                    'parentId' => $commentId,
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'message' => 'Test Reply',
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added comment on idea "Rely on me 1"',
                        $expectedPerformer . ' added comment on idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Message:') . ' ' . Html::multiline('Test Reply')
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    /**
     * @dataProvider getIsLoggedInData
     * @param bool $isLoggedIn
     */
    public function testAddCommentWithMention($isLoggedIn)
    {
        $this->toggleLogin($isLoggedIn);
        $this->addSubscriberUser();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'ideacomment',
                'row' => [
                    'id' => 'fakeCommentId',
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'message' => 'Test Comment',
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideacommentmention',
                'row' => [
                    'id' => 'fakeCommentMentionId',
                    'ideaCommentId' => 'fakeCommentId',
                    'userId' => self::SUBSCRIBER_USER_ID,
                ],
            ],
        ]);
        $commentId = $guids->substitute('fakeCommentId');
        $this->_appMock->sendNotifications();
        $expectedPerformer = $isLoggedIn ? 'Performer' : 'Guest';
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added comment on idea "Rely on me 1"',
                        $expectedPerformer . ' added comment on idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Recipients:') . ' Subscriber',
                        Html::bold('Message:') . ' ' . Html::multiline('Test Comment'),
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'ideacomment',
                'row' => [
                    'id' => 'fakeReplyId',
                    'parentId' => $commentId,
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'message' => 'Test Reply',
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideacommentmention',
                'row' => [
                    'id' => 'fakeReplyMentionId',
                    'ideaCommentId' => 'fakeReplyId',
                    'userId' => self::SUBSCRIBER_USER_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        $expectedPerformer . ' added comment on idea "Rely on me 1"',
                        $expectedPerformer . ' added comment on idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;',
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Recipients:') . ' Subscriber',
                        Html::bold('Message:') . ' ' . Html::multiline('Test Reply'),
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    public function testAddCommentToMyIdea()
    {
        $this->addSubscriberUser();
        $this->login();

        $ideaId = '256';
        $this->_dbMock->addMockRows([
            new Idea([
                'id' => $ideaId,
                'summary' => 'New Idea',
                'userId' => self::SUBSCRIBER_USER_ID,
                'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
            ]),
        ]);

        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'ideacomment',
                'row' => [
                    'id' => 'fakeCommentId',
                    'ideaId' => $ideaId,
                    'message' => 'Test Comment',
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer added comment on your idea "New Idea"',
                        'Performer added comment on your idea &quot;<a href="http://localhost:8080/idea/' . $ideaId . '">New Idea</a>&quot;',
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Message:') . ' ' . Html::multiline('Test Comment')
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region IdeaSubscription, TagSubscription, CategorySubscription, UserSubscription

    public function getTestSubscriptionData()
    {
        return [
            'subscribe to idea' => [
                'idea', self::RELATION_IDEA_ID1, true,
                [
                    'Performer subscribed to idea "Rely on me 1"',
                    'Performer subscribed to idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;'
                ]
            ],
            'unsubscribe from idea' => [
                'idea', self::RELATION_IDEA_ID1, null,
                [
                    'Performer not subscribed to idea "Rely on me 1"',
                    'Performer not subscribed to idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;'
                ]
            ],
            'ignore idea' => [
                'idea', self::RELATION_IDEA_ID1, false,
                [
                    'Performer ignores idea "Rely on me 1"',
                    'Performer ignores idea &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;'
                ]
            ],
            'subscribe to category' => [
                'category', self::CATEGORY_LIVERPOOL_ID, true,
                [
                    'Performer subscribed to category "Europe > England > Liverpool"',
                    'Performer subscribed to category &quot;<a href="http://localhost:8080/category/' . self::CATEGORY_LIVERPOOL_ID . '">Europe &gt; England &gt; Liverpool</a>&quot;'
                ]
            ],
            'unsubscribe from category' => [
                'category', self::CATEGORY_LIVERPOOL_ID, null,
                [
                    'Performer not subscribed to category "Europe > England > Liverpool"',
                    'Performer not subscribed to category &quot;<a href="http://localhost:8080/category/' . self::CATEGORY_LIVERPOOL_ID . '">Europe &gt; England &gt; Liverpool</a>&quot;'
                ]
            ],
            'ignore category' => [
                'category', self::CATEGORY_LIVERPOOL_ID, false,
                [
                    'Performer ignores category "Europe > England > Liverpool"',
                    'Performer ignores category &quot;<a href="http://localhost:8080/category/' . self::CATEGORY_LIVERPOOL_ID . '">Europe &gt; England &gt; Liverpool</a>&quot;'
                ]
            ],
            'subscribe to tag' => [
                'tag', DbMock::TAG_FEATURE_ID, true,
                'Performer subscribed to tag "feature"'
            ],
            'unsubscribe from tag' => [
                'tag', DbMock::TAG_FEATURE_ID, null,
                'Performer not subscribed to tag "feature"'
            ],
            'ignore tag' => [
                'tag', DbMock::TAG_FEATURE_ID, false,
                'Performer ignores tag "feature"'
            ],
            'subscribe to user' => [
                'user', self::SUBSCRIBER_USER_ID, true,
                'Performer subscribed to user "Subscriber"'
            ],
            'unsubscribe from user' => [
                'user', self::SUBSCRIBER_USER_ID, null,
                'Performer not subscribed to user "Subscriber"'
            ],
            'ignore user' => [
                'user', self::SUBSCRIBER_USER_ID, false,
                'Performer ignores user "Subscriber"'
            ],
        ];
    }

    /**
     * @dataProvider getTestSubscriptionData
     * @param string $tableName
     * @param string $id
     * @param bool $included
     * @param string|string[] $expectedSubject
     */
    public function testSubscription($tableName, $id, $included, $expectedSubject)
    {
        $this->addSubscriberUser();
        $this->login();

        $fieldName = $tableName === 'user' ? 'dstUserId' : $tableName . 'Id';
        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => $tableName . 'subscription',
                'row' => [
                    'id' => 'fakeSubscriptionId',
                    $fieldName => $id,
                    'included' => $included,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification($expectedSubject, self::EXPECTED_REASON),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region IdeaVote

    public function getTestVoteData()
    {
        return [
            'nothing -> vote' => [null, 'add', true, 'voted for idea'],
            'nothing -> un-vote' => [null, 'add', false, 'voted against idea'],
            'un-vote -> vote' => [false, 'update', true, 'voted for idea'],
            'vote -> un-vote' => [true, 'update', false, 'voted against idea'],
            'vote -> nothing' => [true, 'delete', null, 'removed the vote for idea'],
            'un-vote -> nothing' => [false, 'delete', null, 'removed the vote for idea'],
        ];
    }

    /**
     * @dataProvider getTestVoteData
     * @param bool|null $prevIsPositive
     * @param string $action
     * @param bool|null $isPositive
     * @param string $expectedAction
     */
    public function testVote($prevIsPositive, $action, $isPositive, $expectedAction)
    {
        $this->addSubscriberUser();
        $this->login();

        if ($prevIsPositive !== null) {
            $voteId = '1';
            $this->_dbMock->addMockRows([
                new IdeaVote([
                    'id' => $voteId,
                    'userId' => self::PERFORMER_USER_ID,
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'isPositive' => $isPositive,
                ]),
            ]);
        } else {
            $voteId = 'fakeVoteId';
        }

        AllEntities::save([
            [
                'type' => $action,
                'tableName' => 'ideavote',
                'row' => [
                    'id' => $voteId,
                    'ideaId' => self::RELATION_IDEA_ID1,
                    'isPositive' => $isPositive,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer ' . $expectedAction . ' "Rely on me 1"',
                        'Performer ' . $expectedAction . ' &quot;<a href="http://localhost:8080/idea/' . self::RELATION_IDEA_ID1 . '">Rely on me 1</a>&quot;'
                    ],
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region Category

    public function getTestCategoryData()
    {
        return [
            'root category' => [
                null, '',
                Html::diffAdd('<a href="http://localhost:8080/category/' . self::CATEGORY_LONDON_ID . '">Europe &gt; England &gt; London</a>')
            ],
            'child category' => [
                self::CATEGORY_BERLIN_ID, 'Europe > Germany > Berlin > ',
                Html::diffDelete('<a href="http://localhost:8080/category/' . self::CATEGORY_BERLIN_ID . '">Europe &gt; Germany &gt; Berlin</a>') . Html::diffAdd('<a href="http://localhost:8080/category/' . self::CATEGORY_LONDON_ID . '">Europe &gt; England &gt; London</a>')
            ],
        ];
    }

    /**
     * @dataProvider getTestCategoryData
     * @param string|null $parentId
     * @param string $expectedPathPrefix
     * @param string $expectedParentChangeHtml
     */
    public function testCategory($parentId, $expectedPathPrefix, $expectedParentChangeHtml)
    {
        $this->addSubscriberUser();
        $this->login();

        $guids = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'category',
                'row' => [
                    'id' => 'fakeCategoryId',
                    'summary' => 'Test Category',
                    'parentId' => $parentId,
                ],
            ],
        ]);
        $categoryId = $guids->substitute('fakeCategoryId');
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer added category ' . $expectedPathPrefix . 'Test Category',
                        'Performer added category <a href="http://localhost:8080/category/' . $categoryId . '">' . htmlspecialchars($expectedPathPrefix) . 'Test Category</a>'
                    ],
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'category',
                'row' => [
                    'id' => $categoryId,
                    'summary' => 'Test New Category',
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated category ' . $expectedPathPrefix . 'Test New Category',
                        'Performer updated category <a href="http://localhost:8080/category/' . $categoryId . '">' . htmlspecialchars($expectedPathPrefix) . 'Test New Category</a>'
                    ],
                    self::EXPECTED_REASON,
                    Html::bold('Summary:') . ' Test ' . Html::diffAdd('New ') . 'Category'
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'category',
                'row' => [
                    'id' => $categoryId,
                    'summary' => 'Updated Category',
                    'parentId' => self::CATEGORY_LONDON_ID,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    [
                        'Performer updated category Europe > England > London > Updated Category',
                        'Performer updated category <a href="http://localhost:8080/category/' . $categoryId . '">Europe &gt; England &gt; London &gt; Updated Category</a>'
                    ],
                    self::EXPECTED_REASON,
                    [
                        Html::bold('Summary:') . ' ' . Html::diffDelete('Test New') . Html::diffAdd('Updated') . ' Category',
                        Html::bold('Parent:') . ' ' . $expectedParentChangeHtml,
                    ]
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'delete',
                'tableName' => 'category',
                'row' => ['id' => $categoryId],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer removed category Europe > England > London > Updated Category',
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region IdeaStatus, Tag, Relation

    public function getTestAddDictionaryItemData()
    {
        return [
            'idea status' => ['ideastatus', 'Backlog', 'Performer added idea status Backlog'],
            'tag' => ['tag', 'enhancement', 'Performer added tag enhancement'],
            'relation' => ['relation', 'blocks', 'Performer added relation type blocks'],
        ];
    }

    /**
     * @dataProvider getTestAddDictionaryItemData
     * @param string $tableName
     * @param string $text
     * @param string $expectedSubject
     */
    public function testAddDictionaryItem($tableName, $text, $expectedSubject)
    {
        $this->addSubscriberUser();
        $this->login();

        AllEntities::save([
            [
                'type' => 'add',
                'tableName' => $tableName,
                'row' => [
                    'id' => 'fakeId',
                    'summary' => $text,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    $expectedSubject,
                    self::EXPECTED_REASON
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion

    // region User

    public function getTestUpdateUserData()
    {
        return [
            'update name -> admin user' => [
                true,
                ['name' => 'Vasya'],
                Html::bold('Name:') . ' ' . Html::diffDelete('Performer') . Html::diffAdd('Vasya')
            ],
            'update name -> regular user' => [
                false,
                ['name' => 'Vasya'],
                Html::bold('Name:') . ' ' . Html::diffDelete('Performer') . Html::diffAdd('Vasya')
            ],
            'update photo -> admin user' => [
                true,
                ['avatarUrl' => 'https://bit.ly/asdf.png'],
                Html::bold('Uploaded new photo.')
            ],
            'update photo -> regular user' => [
                false,
                ['avatarUrl' => 'https://bit.ly/asdf.png'],
                Html::bold('Uploaded new photo.')
            ],
            'update password -> admin user' => [
                true,
                ['passwordHash' => 'root'],
                Html::bold('Updated password.')
            ],
            'update password -> regular user' => [
                false,
                ['passwordHash' => 'root'],
                null
            ],
            'update subscription settings -> admin user' => [
                true,
                ['subscribeToAllNewIdeas' => true],
                Html::bold('Changed subscription settings.')
            ],
            'update subscription settings -> regular user' => [
                false,
                ['subscribeToAllNewIdeas' => true],
                null
            ],
            'update all -> admin user' => [
                true,
                [
                    'name' => 'Vasya',
                    'avatarUrl' => 'https://bit.ly/asdf.png',
                    'passwordHash' => 'root',
                    'subscribeToAllNewIdeas' => true,
                ],
                [
                    Html::bold('Name:') . ' ' . Html::diffDelete('Performer') . Html::diffAdd('Vasya'),
                    Html::bold('Uploaded new photo.'),
                    Html::bold('Updated password.'),
                    Html::bold('Changed subscription settings.'),
                ]
            ],
            'update all -> regular user' => [
                false,
                [
                    'name' => 'Vasya',
                    'avatarUrl' => 'https://bit.ly/asdf.png',
                    'passwordHash' => 'root',
                    'subscribeToAllNewIdeas' => true,
                ],
                [
                    Html::bold('Name:') . ' ' . Html::diffDelete('Performer') . Html::diffAdd('Vasya'),
                    Html::bold('Uploaded new photo.'),
                ]
            ],
        ];
    }

    /**
     * @dataProvider getTestUpdateUserData
     * @param bool $isSubscriberAdmin
     * @param array $updates
     * @param string[]|string|null $expectedContent
     */
    public function testUpdateUser($isSubscriberAdmin, $updates, $expectedContent)
    {
        $this->addSubscriberUser([
            'isAdmin' => $isSubscriberAdmin,
        ]);
        $this->login();

        $expectedName = $updates['name'] ?? 'Performer';

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'user',
                'row' => array_merge(['id' => self::PERFORMER_USER_ID], $updates),
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            $expectedContent
                ? [
                    $this->buildExpectedNotification(
                        $expectedName . ' updated the profile',
                        self::EXPECTED_REASON,
                        $expectedContent
                    ),
                ]
                : [],
            $this->_emailMock->popEmails()
        );
    }

    public function testChangePhoto()
    {
        $this->addSubscriberUser();
        $this->login();

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'user',
                'row' => [
                    'id' => self::PERFORMER_USER_ID,
                    'avatarUrl' => 'https://bit.ly/asdf.png',
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer updated the profile',
                    self::EXPECTED_REASON,
                    Html::bold('Uploaded new photo.')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'user',
                'row' => [
                    'id' => self::PERFORMER_USER_ID,
                    'avatarUrl' => 'https://bit.ly/qwerty.png',
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer updated the profile',
                    self::EXPECTED_REASON,
                    Html::bold('Uploaded new photo.')
                ),
            ],
            $this->_emailMock->popEmails()
        );

        AllEntities::save([
            [
                'type' => 'update',
                'tableName' => 'user',
                'row' => [
                    'id' => self::PERFORMER_USER_ID,
                    'avatarUrl' => null,
                ],
            ],
        ]);
        $this->_appMock->sendNotifications();
        Assert::assertEquals(
            [
                $this->buildExpectedNotification(
                    'Performer updated the profile',
                    self::EXPECTED_REASON,
                    Html::bold('Removed the photo.')
                ),
            ],
            $this->_emailMock->popEmails()
        );
    }

    // endregion
}
