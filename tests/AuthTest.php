<?php /** @noinspection SpellCheckingInspection */

namespace Idimption\Test;

use Idimption\Auth;
use Idimption\Entity\User;
use Idimption\Exception\HttpException;
use PHPUnit\Framework\Assert;

class AuthTest extends BaseTest
{
    const ADMIN_USER_ID = '1';
    const ADMIN_USER_EMAIL = 'admin@mail.com';
    const ADMIN_USER_PASSWORD = 'root';

    const REGULAR_USER_ID = '2';
    const REGULAR_USER_EMAIL = 'regular@mail.com';
    const REGULAR_USER_PASSWORD = 'qwerty';

    const UNVERIFIED_USER_ID = '3';
    const UNVERIFIED_USER_EMAIL = 'unverified@mail.com';
    const UNVERIFIED_USER_PASSWORD = 'test';

    const NOPASSWORD_USER_ID = '4';
    const NOPASSWORD_USER_EMAIL = 'nopassword@mail.com';

    const DB_AUTO_INCREMENT_ID = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_dbMock->addMockRows([
            new User([
                'id' => self::ADMIN_USER_ID,
                'email' => self::ADMIN_USER_EMAIL,
                'name' => 'Admin',
                'verifiedEmail' => true,
                'passwordHash' => Auth::getPasswordHash(self::ADMIN_USER_PASSWORD),
                'isAdmin' => true,
                'subscribeToAll' => true,
            ]),
            new User([
                'id' => self::REGULAR_USER_ID,
                'email' => self::REGULAR_USER_EMAIL,
                'name' => 'Regular',
                'verifiedEmail' => true,
                'passwordHash' => Auth::getPasswordHash(self::REGULAR_USER_PASSWORD),
            ]),
            new User([
                'id' => self::UNVERIFIED_USER_ID,
                'email' => self::UNVERIFIED_USER_EMAIL,
                'name' => 'Not verified',
                'verifiedEmail' => false,
                'passwordHash' => Auth::getPasswordHash(self::UNVERIFIED_USER_PASSWORD),
            ]),
            new User([
                'id' => self::NOPASSWORD_USER_ID,
                'email' => self::NOPASSWORD_USER_EMAIL,
                'name' => 'No password',
                'verifiedEmail' => true,
            ]),
        ]);

        $this->_dbMock->autoIncrementId = self::DB_AUTO_INCREMENT_ID;
    }

    public function getUserIds()
    {
        return [
            'admin' => [self::ADMIN_USER_ID],
            'regular user' => [self::REGULAR_USER_ID],
            'unverified user' => [self::UNVERIFIED_USER_ID],
            'no password user' => [self::NOPASSWORD_USER_ID],
            'anonymous' => [null],
        ];
    }

    /**
     * @dataProvider getUserIds
     * @param string|null $userId
     */
    public function testAuthCheckBasic($userId)
    {
        Auth::setLoggedInUserId($userId);

        Assert::assertEquals($userId, Auth::getLoggedInUserId());
    }

    public function testAuthCheckAfterRemovingUser()
    {
        // User is logged in, the auth check should return the user ID as usual
        Auth::setLoggedInUserId(self::REGULAR_USER_ID);
        Assert::assertEquals(self::REGULAR_USER_ID, Auth::getLoggedInUserId());

        // User was removed, the user should be logged out automatically
        $user = new User(['id' => self::REGULAR_USER_ID]);
        $user->delete();
        Assert::assertEquals(null, Auth::getLoggedInUserId());
    }

    public function getTestLoginData()
    {
        return [
            // success with correct password
            'admin successful login' => [self::ADMIN_USER_EMAIL, self::ADMIN_USER_PASSWORD, self::ADMIN_USER_ID],
            'regular user successful login' => [self::REGULAR_USER_EMAIL, self::REGULAR_USER_PASSWORD, self::REGULAR_USER_ID],
            'not verified user successful login' => [self::UNVERIFIED_USER_EMAIL, self::UNVERIFIED_USER_PASSWORD, self::UNVERIFIED_USER_ID],

            // fail with wrong password
            'wrong non-existing password' => [self::ADMIN_USER_EMAIL, 'C%^YE%HVHT', 'Wrong password'],
            'password of other user' => [self::REGULAR_USER_EMAIL, self::ADMIN_USER_PASSWORD, 'Wrong password'],
            'empty password for regular user' => [self::REGULAR_USER_EMAIL, '', 'Wrong password'],
            'null password for regular user' => [self::REGULAR_USER_EMAIL, null, 'Wrong password'],
            'SQL injection password' => [self::REGULAR_USER_EMAIL, '%" OR "_" <> "', 'Wrong password'],
            'random password for user with no password' => [self::NOPASSWORD_USER_EMAIL, 'V*%&Vte56ve5', 'Wrong password'],
            'empty password for user with no password' => [self::NOPASSWORD_USER_EMAIL, '', 'Wrong password'],
            'null password for user with no password' => [self::NOPASSWORD_USER_EMAIL, null, 'Wrong password'],

            // fail with wrong email
            'non-existing email and non-existing password' => ['nx@mail.com', '%CE%T^E%', 'User not found: nx@mail.com'],
            'non-existing email and password of other user' => ['nx@mail.com', self::REGULAR_USER_PASSWORD, 'User not found: nx@mail.com'],
            'empty email' => ['', 'C%Y&VHvytd', 'User not found: '],
        ];
    }

    /**
     * @dataProvider getTestLoginData
     * @param string $email
     * @param string $password
     * @param string $expectedResult
     */
    public function testLogin($email, $password, $expectedResult)
    {
        try {
            $expectedUserId = $expectedResult;
            Assert::assertEquals($expectedUserId, Auth::login($email, $password));
            Assert::assertEquals($expectedUserId, Auth::getLoggedInUserId());
        } catch (HttpException $exception) {
            $expectedErrorMessage = $expectedResult;
            Assert::assertEquals($expectedErrorMessage, $exception->getMessage());
            Assert::assertEquals(null, Auth::getLoggedInUserId());
        }
    }

    /**
     * @dataProvider getUserIds
     * @param string|null $userId
     */
    public function testLogout($userId)
    {
        Auth::setLoggedInUserId($userId);

        Auth::logout();

        Assert::assertEquals(null, Auth::getLoggedInUserId());
    }

    public function testRegisterSuccess()
    {
        $expectedUserId = (string)(self::DB_AUTO_INCREMENT_ID + 1);
        Assert::assertEquals($expectedUserId, Auth::register('new@mail.com', 'New', '123456'));
        Assert::assertEquals($expectedUserId, Auth::getLoggedInUserId());
        $user = User::getInstance()->getRowById($expectedUserId);
        Assert::assertEquals(
            [
                'id' => $expectedUserId,
                'email' => 'new@mail.com',
                'name' => 'New',
                'passwordHash' => Auth::getPasswordHash('123456'),
                'verifiedEmail' => false,
                'avatarUrl' => null,
                'isAdmin' => false,
                'subscribeToAll' => false,
                'subscribeToAllNewIdeas' => false,
                'subscribeToUpdatesInMyIdeas' => true,
                'subscribeToCommentsOnMyIdeas' => true,
                'subscribeToReplyComments' => true,
                'subscribeToMentionComments' => true,
                'subscribeToVotesInMyIdeas' => true,
                'subscribeToWatchesInMyIdeas' => true,
                'subscribeToUnwatchesInMyIdeas' => true,
            ],
            $user->toArray(true)
        );

        $this->_appMock->sendNotifications();
        $newUserVerificationCode = $user->getVerificationCode();
        $adminUserVerificationCode = User::getInstance()->getRowById(self::ADMIN_USER_ID)->getVerificationCode();
        Assert::assertEquals(
            [
                [
                    'subject' => 'Email verification for Idimption',
                    'content' => implode("\n", [
                        "<p>Hi New,</p>",
                        "<p>To verify your email for Idimption, please follow <a href='http://localhost:8080/auth/verify/$newUserVerificationCode?r=/'>this link</a>.</p>",
                        "<p>If you didn't register on Idimption with this email, then please just ignore this email.</p>",
                        "<p>Cheers,<br>Yura from Idimption.</p>"
                    ]),
                    'to' => ['new@mail.com'],
                    'cc' => [],
                    'bcc' => [],
                    'queue' => false,
                ],
                [
                    'subject' => 'New registered',
                    'content' => implode("\n", [
                        "<h2>New registered</h2>",
                        "<p>You received this email because you are subscribed to all updates. <a href='http://localhost:8080/auth/verify/$adminUserVerificationCode?r=/profile'>Manage subscription settings</a>.</p>",
                    ]),
                    'to' => [self::ADMIN_USER_EMAIL],
                    'cc' => [],
                    'bcc' => [],
                    'queue' => true,
                ],
            ],
            $this->_emailMock->popEmails()
        );
    }

    public function testRegisterFail()
    {
        try {
            Auth::register(self::REGULAR_USER_EMAIL, 'Regular', 'qwerty');
            Assert::fail('Expected to throw exception');
        } catch (HttpException $exception) {
            Assert::assertEquals('User regular@mail.com already exists', $exception->getMessage());
        }
        Assert::assertEquals(null, Auth::getLoggedInUserId());
        $expectedUserId = (string)(self::DB_AUTO_INCREMENT_ID + 1);
        Assert::assertEquals(null, User::getInstance()->getRowById($expectedUserId));
        $this->_appMock->sendNotifications();
        Assert::assertEquals([], $this->_emailMock->popEmails());
    }

    public function getSendVerificationEmailTestData()
    {
        return [
            'regular user' => [self::REGULAR_USER_EMAIL, 'Regular'],
            'unverified user' => [self::UNVERIFIED_USER_EMAIL, 'Not verified'],
            'no password user' => [self::NOPASSWORD_USER_EMAIL, 'No password'],
        ];
    }

    /**
     * @dataProvider getSendVerificationEmailTestData
     * @param string $email
     * @param string $name
     */
    public function testSendResetPasswordEmail($email, $name)
    {
        Auth::sendVerificationCode($email, true);

        $verificationCode = User::getInstance()->getRowByEmail($email)->getVerificationCode();

        Assert::assertEquals(
            [
                [
                    'subject' => 'Email verification for Idimption',
                    'content' => implode("\n", [
                        "<p>Hi $name,</p>",
                        "<p>To reset your password, please follow <a href='http://localhost:8080/auth/verify/$verificationCode?r=/profile'>this link</a>.</p>",
                        "<p>If you didn't register on Idimption with this email, then please just ignore this email.</p>",
                        "<p>Cheers,<br>Yura from Idimption.</p>"
                    ]),
                    'to' => [$email],
                    'cc' => [],
                    'bcc' => [],
                    'queue' => false,
                ],
            ],
            $this->_emailMock->popEmails()
        );
    }

    /**
     * @dataProvider getSendVerificationEmailTestData
     * @param string $email
     * @param string $name
     */
    public function testSendVerificationEmailAnonymous($email, $name)
    {
        Auth::sendVerificationCode($email, false);

        $verificationCode = User::getInstance()->getRowByEmail($email)->getVerificationCode();

        Assert::assertEquals(
            [
                [
                    'subject' => 'Email verification for Idimption',
                    'content' => implode("\n", [
                        "<p>Hi $name,</p>",
                        "<p>To verify your email for Idimption, please follow <a href='http://localhost:8080/auth/verify/$verificationCode?r=/'>this link</a>.</p>",
                        "<p>If you didn't register on Idimption with this email, then please just ignore this email.</p>",
                        "<p>Cheers,<br>Yura from Idimption.</p>"
                    ]),
                    'to' => [$email],
                    'cc' => [],
                    'bcc' => [],
                    'queue' => false,
                ],
            ],
            $this->_emailMock->popEmails()
        );
    }

    /**
     * @dataProvider getSendVerificationEmailTestData
     * @param string $email
     * @param string $name
     */
    public function testSendVerificationEmailLoggedIn($email, $name)
    {
        $user = User::getInstance()->getRowByEmail($email);
        $verificationCode = $user->getVerificationCode();

        Auth::setLoggedInUserId($user->id);
        Auth::sendVerificationCode(null, false);

        Assert::assertEquals(
            [
                [
                    'subject' => 'Email verification for Idimption',
                    'content' => implode("\n", [
                        "<p>Hi $name,</p>",
                        "<p>To verify your email for Idimption, please follow <a href='http://localhost:8080/auth/verify/$verificationCode?r=/'>this link</a>.</p>",
                        "<p>If you didn't register on Idimption with this email, then please just ignore this email.</p>",
                        "<p>Cheers,<br>Yura from Idimption.</p>"
                    ]),
                    'to' => [$email],
                    'cc' => [],
                    'bcc' => [],
                    'queue' => false,
                ],
            ],
            $this->_emailMock->popEmails()
        );
    }

    public function testSendResetPasswordEmailNonExistingEmailFail()
    {
        try {
            Auth::sendVerificationCode('nx@mail.com', true);
            Assert::fail('Expected to throw an error');
        } catch (HttpException $exception) {
            Assert::assertEquals('User not found: nx@mail.com', $exception->getMessage());
        }
    }

    public function testSendVerificationEmailNonExistingEmailFail()
    {
        try {
            Auth::sendVerificationCode('nx@mail.com', false);
            Assert::fail('Expected to throw an error');
        } catch (HttpException $exception) {
            Assert::assertEquals('User not found: nx@mail.com', $exception->getMessage());
        }
    }

    public function testSendVerificationEmailEmptyEmailFromAnonymousFail()
    {
        try {
            Auth::sendVerificationCode(null, false);
            Assert::fail('Expected to throw an error');
        } catch (HttpException $exception) {
            Assert::assertEquals('User not found: ', $exception->getMessage());
        }
    }

    public function getVerifyEmailSuccessTestData()
    {
        return [
            'admin user' => [self::ADMIN_USER_ID, false],
            'unverified user' => [self::UNVERIFIED_USER_ID, true],
            'regular user' => [self::REGULAR_USER_ID, false],
            'no password user' => [self::NOPASSWORD_USER_ID, false],
        ];
    }

    /**
     * @dataProvider getVerifyEmailSuccessTestData
     * @param string $userId
     * @param bool $expectedVerifiedNow
     */
    public function testVerifyEmailSuccess($userId, $expectedVerifiedNow)
    {
        $verificationCode = User::getInstance()->getRowById($userId)->getVerificationCode();
        $result = Auth::verifyEmail($verificationCode);
        Assert::assertEquals($userId, $result['userId']);
        Assert::assertEquals($expectedVerifiedNow, $result['verifiedNow']);
        Assert::assertEquals(true, User::getInstance()->getRowById($userId)->verifiedEmail);
    }

    public function getVerifyEmailFailTestData()
    {
        return [
            'empty code' => [''],
            'non-existing code' => ['5t8vhsoe9vsoemo8hrd8ohtmodro8gvd7hrmo8gv'],
        ];
    }

    /**
     * @dataProvider getVerifyEmailFailTestData
     * @param string $code
     */
    public function testVerifyEmailFail($code)
    {
        try {
            Auth::verifyEmail($code);
            Assert::fail('Expected to throw an error');
        } catch (HttpException $exception) {
            Assert::assertEquals('Verification code expired', $exception->getMessage());
        }
    }
}
