<?php

namespace Idimption\Entity;

use Idimption\App;
use Idimption\Auth;
use Idimption\Db;
use Idimption\Exception\InternalServerErrorException;

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
     * @diffable
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
    public $subscribeToCommentsOnMyIdeas = true;

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


    public function __construct($data = [])
    {
        parent::__construct($data, 'user');
    }

    public function getEntityName(User $recipient = null)
    {
        return 'user';
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

    public function getVerificationUrl($returnUrl = '/')
    {
        return App::getInstance()->getFrontEndUri('/auth/verify/' . urlencode($this->getVerificationCode()) . '?r=' . urldecode($returnUrl));
    }

    public function getVerificationCode()
    {
        return sha1($this->email . App::getInstance()->getConfig('salt'));
    }

    /**
     * @param string $verificationCode
     * @return static|null
     */
    public function getRowByVerificationCode($verificationCode)
    {
        /** @var static $user */
        foreach ($this->getAllRows() as $user) {
            if ($user->getVerificationCode() === $verificationCode) {
                return $user;
            }
        }

        return null;
    }

    public function adminUpdate($data)
    {
        Db::getInstance()->updateRow('user', $this->id, $data);
        foreach ($data as $fieldName => $fieldValue) {
            $this->$fieldName = $fieldValue;
        }
        // Don't need to clear the cache, cause the object is up to date now
    }

    public function getChangeSummary(RowChange $change, User $recipient, $isHtml)
    {
        if (!Auth::getLoggedInUserId()) {
            return 'registered';
        }

        /** @var static $row */
        $row = $change->getInfoRow();
        $isCurrentUser = $row->id === Auth::getLoggedInUserId();

        switch ($change->action) {
            case EntityUpdateAction::INSERT:
                return $isCurrentUser ? 'registered' : 'invited at ' . $row->name;
            case EntityUpdateAction::UPDATE:
                $action = 'updated the profile';
                break;
            case EntityUpdateAction::DELETE:
                $action = 'removed the profile';
                break;
            default:
                throw new InternalServerErrorException();
        }

        if (!$isCurrentUser) {
            $action .= ' of ' . $row->name;
        }

        return $isHtml ? htmlspecialchars($action) : $action;
    }

    public function formatChange(RowChange $change, User $recipient)
    {
        if ($change->action !== EntityUpdateAction::UPDATE) {
            return '';
        }

        $html = '';
        $html .= $this->formatChangeField($change, 'name', 'Name');
        $avatarChange = $change->getFieldChange('avatarUrl');
        if ($avatarChange) {
            $html .= $this->formatChangeFieldWrapper($avatarChange->toText ? 'Uploaded new photo' : 'Removed the photo', '', false, true);
        }
        if ($recipient->isAdmin) {
            if ($change->getFieldChange('passwordHash')) {
                $html .= $this->formatChangeFieldWrapper('Updated password', '', false, true);
            }
            $verificationChange = $change->getFieldChange('verifiedEmail');
            if ($verificationChange && $verificationChange->toText === 'yes') {
                $html .= $this->formatChangeFieldWrapper('Verified the email', '', false, true);
            }
            foreach ($this->getVisibleFields() as $fieldName) {
                if (substr($fieldName, 0, 11) === 'subscribeTo' && $change->getFieldChange($fieldName)) {
                    $html .= $this->formatChangeFieldWrapper('Changed subscription settings', '', false, true);
                    break;
                }
            }
        }
        return $html;
    }
}
