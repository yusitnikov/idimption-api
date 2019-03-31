<?php

namespace Idimption\Entity;

use Idimption\Db;

class User extends BaseEntity
{
    /**
     * @var string
     * @readOnly
     * @additionalInfoField
     * @hook UserId
     */
    public $id = '';

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
     */
    public $verificationCode;

    public function __construct()
    {
        parent::__construct('user');
    }

    public function getVisibleFields()
    {
        return ['id', 'name', 'avatarUrl', 'passwordHash', 'verifiedEmail'];
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
