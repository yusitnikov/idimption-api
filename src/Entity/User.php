<?php

namespace Idimption\Entity;

use Idimption\Db;

class User extends BaseEntity
{
    /**
     * @var int
     * @readOnly
     * @hook UserIdKey
     */
    public $id;

    /**
     * @var string
     * @readOnly
     * @additionalInfoField
     * @hidden
     */
    public $email = '';

    /**
     * @var string
     * @displayField
     */
    public $name = '';

    /**
     * @var string|null
     */
    public $avatarUrl;

    /**
     * @var string
     * @hook PasswordHash
     */
    public $passwordHash;

    /**
     * @var bool
     * @readOnly
     * @hook Ignore
     */
    public $verifiedEmail = false;

    /**
     * @var string
     * @hidden
     */
    public $verificationCode;

    /**
     * @var bool
     * @readOnly
     * @hook Ignore
     */
    public $isAdmin = false;


    /**
     * @var bool
     */
    public $subscribeToAll = false;

    /**
     * @var bool
     */
    public $subscribeToAllNewIdeas = false;

    /**
     * @var bool
     */
    public $subscribeToUpdatesInMyIdeas = true;

    /**
     * @var bool
     */
    public $subscribeToUpdatesInIdeasWatching = true;

    /**
     * @var bool
     */
    public $subscribeToCommentsOnMyIdeas = true;

    /**
     * @var bool
     */
    public $subscribeToCommentsOnIdeasWatching = true;

    /**
     * @var bool
     */
    public $subscribeToReplyComments = true;

    /**
     * @var bool
     */
    public $subscribeToMentionComments = true;

    /**
     * @var bool
     */
    public $subscribeToVotesInMyIdeas = true;

    /**
     * @var bool
     */
    public $subscribeToWatchesInMyIdeas = true;

    /**
     * @var bool
     */
    public $subscribeToUnwatchesInMyIdeas = true;


    public function __construct()
    {
        parent::__construct('user');
    }

    public function save($action, $disableHooks = false, $updateFields = [], $log = true)
    {
        if (in_array('password', $updateFields)) {
            $updateFields[] = 'passwordHash';
        }
        parent::save($action, $disableHooks, $updateFields, $log);
    }

    public function jsonUnserialize($array)
    {
        if (!empty($array['password'])) {
            $array['passwordHash'] = $array['password'];
        }
        parent::jsonUnserialize($array);
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        unset($json['passwordHash']);
        return $json;
    }

    /**
     * @param string $email
     * @return static|null
     */
    public function getRowByEmail($email)
    {
        return $this->getRowsMap(['email'])[$email] ?? null;
    }

    /**
     * @param string $verificationCode
     * @return static|null
     */
    public function getRowByVerificationCode($verificationCode)
    {
        return $this->getRowsMap(['verificationCode'])[$verificationCode] ?? null;
    }

    public function adminUpdate($data)
    {
        Db::updateRow('user', $this->id, $data);
        foreach ($data as $fieldName => $fieldValue) {
            $this->$fieldName = $fieldValue;
        }
        // Don't need to clear the cache, cause the object is up to date now
    }
}
