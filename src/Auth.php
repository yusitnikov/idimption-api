<?php

namespace Idimption;

use Idimption\Entity\User;
use Idimption\Exception\AccessDeniedException;
use Idimption\Exception\BadRequestException;

class Auth
{
    /**
     * @param string $userId
     * @return User
     * @throws BadRequestException
     */
    private static function _getUserDataOrDie($userId)
    {
        $user = User::getInstance()->getRowById($userId);
        if (!$user) {
            throw new BadRequestException('User not found: ' . $userId);
        }
        return $user;
    }

    private static function _initSession()
    {
        static $done = false;
        if (!$done) {
            session_start([
                'use_cookies' => true,
                'use_only_cookies' => true,
                'cookie_lifetime' => 0,
                'cookie_path' => '/',
                'cookie_httponly' => true,
            ]);
            $done = true;
        }
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
        self::_initSession();
        $userId = $_SESSION['userId'] ?? null;
        return $userId ? User::getInstance()->getRowById($userId) : null;
    }

    public static function getLoggedInUserId()
    {
        $user = self::getLoggedInUser();
        return $user ? $user->id : null;
    }

    public static function isVerifiedEmail()
    {
        $user = self::getLoggedInUser();
        return $user && $user->verifiedEmail;
    }

    public static function setLoggedInUserId($userId)
    {
        self::_initSession();
        $_SESSION['userId'] = $userId;
    }

    public static function login($userId, $password)
    {
        self::_initSession();
        $user = self::_getUserDataOrDie($userId);
        if ($user->passwordHash !== self::getPasswordHash($password)) {
            throw new BadRequestException('Wrong password');
        }
        self::setLoggedInUserId($userId);
    }

    public static function register($userId, $name, $password)
    {
        $user = new User();
        $user->id = $userId;
        $user->name = $name;
        $user->passwordHash = $password;
        $user->add();

        self::setLoggedInUserId($userId);

        self::sendVerificationCode($userId);
    }

    public static function logout()
    {
        self::setLoggedInUserId(null);
    }

    public static function sendVerificationCode($userId, $resetPassword = false)
    {
        $user = self::_getUserDataOrDie($userId);
        $verificationCode = sha1(mt_rand());
        App::getInstance()->log('Sending verification code for user ' . $userId . ', the code is ' . $verificationCode);
        $user->adminUpdate([
            'verificationCode' => $verificationCode,
        ]);

        $aim = $resetPassword ? 'reset your password' : 'verify your email for Idimption';
        $verificationUrl = App::getInstance()->getFrontEndUri('/auth/verify/' . urlencode($verificationCode) . '/' . (int)$resetPassword);
        Email::send(
            'Email verification for Idimption',
            "
                <p>Hi $user->name,</p>
                <p>To $aim, please follow <a href='$verificationUrl'>this link</a>.</p>
                <p>If you didn't register on Idimption with this email, then please just ignore this email.</p>
                <p>Cheers,<br>Yura from Idimption.</p>
            ",
            [$userId]
        );
    }

    public static function verifyEmail($verificationCode)
    {
        $user = User::getInstance()->getRowByVerificationCode($verificationCode);
        if (!$user) {
            throw new BadRequestException('Verification code expired');
        }
        App::getInstance()->log('Marking email as verified for user ' . $user->id);
        $user->adminUpdate([
            'verifiedEmail' => true,
            'verificationCode' => null,
        ]);
        self::setLoggedInUserId($user->id);
        return $user->id;
    }
}
