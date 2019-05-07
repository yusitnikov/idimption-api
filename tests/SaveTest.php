<?php

namespace Idimption\Test;

use Idimption\Auth;
use Idimption\DbMock;
use Idimption\Entity\AllEntities;
use Idimption\Entity\User;
use PHPUnit\Framework\Assert;

class SaveTest extends BaseTest
{
    const USER_ID = '1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->_dbMock->addMockRows([
            new User([
                'id' => self::USER_ID,
                'email' => 'john.doe@mail.com',
                'name' => 'John Doe',
                'verifiedEmail' => true,
            ]),
        ]);
    }

    public function getUserIds()
    {
        return [
            'logged in' => [self::USER_ID],
            'anonymous' => [null],
        ];
    }

    /**
     * @dataProvider getUserIds
     * @param string|null $userId
     */
    public function testAddSimpleIdea($userId)
    {
        Auth::setLoggedInUserId($userId);

        $guidMap = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Idea 1',
                    'description' => '',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
        ]);

        Assert::assertEquals(['fakeIdeaId' => 1], $guidMap->jsonSerialize());

        Assert::assertEquals([
            [
                'id' => '1',
                'summary' => 'Idea 1',
                'priority' => null,
                'referenceId' => null,
                'userId' => $userId,
                'createdAt' => $this->_appMock->getStartTime(),
                'updatedAt' => $this->_appMock->getStartTime(),
                'description' => '',
                'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
            ],
        ], $this->_dbMock->allEntities['idea']);
    }

    /**
     * @dataProvider getUserIds
     * @param string|null $userId
     */
    public function testAddIdeaWithTags($userId)
    {
        Auth::setLoggedInUserId($userId);

        $guidMap = AllEntities::save([
            [
                'type' => 'add',
                'tableName' => 'idea',
                'row' => [
                    'id' => 'fakeIdeaId',
                    'summary' => 'Idea 1',
                    'description' => '',
                    'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
                ],
            ],
            [
                'type' => 'add',
                'tableName' => 'ideatag',
                'row' => [
                    'id' => 'fakeIdeaTagId',
                    'ideaId' => 'fakeIdeaId',
                    'tagId' => DbMock::TAG_PROJECT_ID,
                ],
            ]
        ]);

        Assert::assertEquals(['fakeIdeaId' => 1, 'fakeIdeaTagId' => 2], $guidMap->jsonSerialize());

        Assert::assertEquals([
            [
                'id' => '1',
                'summary' => 'Idea 1',
                'priority' => null,
                'referenceId' => null,
                'userId' => $userId,
                'createdAt' => $this->_appMock->getStartTime(),
                'updatedAt' => $this->_appMock->getStartTime(),
                'description' => '',
                'statusId' => DbMock::STATUS_NEEDS_REVIEW_ID,
            ],
        ], $this->_dbMock->allEntities['idea']);
        Assert::assertEquals([
            [
                'id' => '2',
                'ideaId' => '1',
                'tagId' => DbMock::TAG_PROJECT_ID,
            ],
        ], $this->_dbMock->allEntities['ideatag']);
    }
}
