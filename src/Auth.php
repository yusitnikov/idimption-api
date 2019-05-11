<?php

namespace Idimption;

use Idimption\Entity\User;
use Idimption\Exception\BadRequestException;

class Auth
{
    use SingletonWithMockTrait;

    protected $_sessionId;
    protected $_sessionData;

    /**
     * @param string|null $email
     * @return User
     * @throws BadRequestException
     */
    private static function _getUserDataOrDie($email = null)
    {
        $user = $email
            ? User::getInstance()->getRowByEmail($email)
            : self::getLoggedInUser();
        if (!$user) {
            throw new BadRequestException('User not found: ' . $email);
        }
        return $user;
    }

    protected static function _initSession()
    {
        $instance = self::getInstance();
        if ($instance->_sessionId === null) {
            if (PHP_SAPI === 'cli') {
                $instance->_sessionId = 0;
                $instance->_sessionData = [];
            } else {
                $instance->_sessionId = App::getInstance()->getParam('sessionId');
                $sessionData = $instance->_sessionId
                    ? Db::getInstance()->selectValue("
                        SELECT data
                        FROM session
                        WHERE id = " . Db::getInstance()->escapeValue($instance->_sessionId) . "
                    ")
                    : null;
                if ($sessionData) {
                    $instance->_sessionData = json_decode($sessionData, true);
                } else {
                    $instance->_sessionId = $instance->_sessionId ?: sha1(mt_rand());
                    $instance->_sessionData = [];
                    Db::getInstance()->insertRow(
                        'session',
                        [
                            'id' => $instance->_sessionId,
                            'data' => [],
                        ],
                        false
                    );
                }
            }
        }
    }

    public static function getSessionId()
    {
        self::_initSession();
        return self::getInstance()->_sessionId;
    }

    protected function _getSessionData()
    {
        self::_initSession();
        return $this->_sessionData;
    }

    protected function _setSessionData($data)
    {
        self::_initSession();
        $this->_sessionData = $data;
        if (PHP_SAPI !== 'cli') {
            Db::getInstance()->updateRow(
                'session',
                $this->_sessionId,
                ['data' => $data],
                false
            );
        }
    }

    protected function _updateSessionData($updates)
    {
        $this->_setSessionData(array_merge($this->_getSessionData(), $updates));
    }

    public static function getPasswordHash($password)
    {
        return sha1($password);
    }

    /**
     * @return User|null
     */
    public static function getLoggedInUser()
    {
        $userId = self::getInstance()->_getSessionData()['userId'] ?? null;
        return $userId ? User::getInstance()->getRowById($userId) : null;
    }

    public static function getLoggedInUserId()
    {
        $user = self::getLoggedInUser();
        return $user ? $user->id : null;
    }

    public static function getLoggedInUserName()
    {
        $user = self::getLoggedInUser();
        return $user ? $user->name : 'Guest';
    }

    public static function isVerifiedEmail()
    {
        $user = self::getLoggedInUser();
        return $user && $user->verifiedEmail;
    }

    public static function isAdmin()
    {
        $user = self::getLoggedInUser();
        return $user && $user->verifiedEmail && $user->isAdmin;
    }

    public static function canEditUsersData($userId)
    {
        return self::isAdmin() || self::isVerifiedEmail() && self::getLoggedInUserId() === $userId;
    }

    public static function setLoggedInUserId($userId)
    {
        self::getInstance()->_updateSessionData(['userId' => $userId]);
    }

    public static function login($email, $password)
    {
        $user = self::_getUserDataOrDie($email);
        if ($user->passwordHash !== self::getPasswordHash($password)) {
            throw new BadRequestException('Wrong password');
        }
        self::setLoggedInUserId($user->id);
        return $user->id;
    }

    public static function register($email, $name, $password)
    {
        if (User::getInstance()->getRowByEmail($email)) {
            throw new BadRequestException('User ' . $email . ' already exists');
        }

        $user = new User();
        $user->email = $email;
        $user->name = $name;
        $user->passwordHash = $password;
        $user->add(true, true);

        self::setLoggedInUserId($user->id);

        self::sendVerificationCode($email);

        return $user->id;
    }

    public static function logout()
    {
        self::setLoggedInUserId(null);
    }

    public static function sendVerificationCode($email = null, $resetPassword = false)
    {
        $user = self::_getUserDataOrDie($email);
        $email = $user->email;
        App::getInstance()->log('Sending verification code for user ' . $user->id . ' (' . $email . ')');

        $aim = $resetPassword ? 'reset your password' : 'verify your email for Idimption';
        $verificationUrl = $user->getVerificationUrl($resetPassword ? '/profile' : '/');
        Email::getInstance()->send(
            'Email verification for Idimption',
            "
                " . Html::emailHeader() . "
                <p>Hi $user->name,</p>
                <p>To $aim, please follow <a href='$verificationUrl'>this link</a>.</p>
                <p>If you didn't register on Idimption with this email, then please just ignore this email.</p>
                " . Html::emailFooter() . "
            ",
            [$email]
        );
    }

    public static function verifyEmail($verificationCode)
    {
        $user = User::getInstance()->getRowByVerificationCode($verificationCode);
        if (!$user) {
            throw new BadRequestException('Verification code expired');
        }
        $wasNotVerified = !$user->verifiedEmail;
        if ($wasNotVerified) {
            App::getInstance()->log('Marking email as verified for user ' . $user->id . ' (' . $user->email . ')');
            $user->adminUpdate([
                'verifiedEmail' => true,
            ]);
        }
        self::setLoggedInUserId($user->id);
        return [
            'userId' => $user->id,
            'verifiedNow' => $wasNotVerified,
        ];
    }
}
