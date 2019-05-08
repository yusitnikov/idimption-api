<?php

namespace Idimption;

use Idimption\Entity\AllEntities;
use Idimption\Entity\BaseEntity;
use Idimption\Entity\EntityUpdateAction;
use Idimption\Entity\User;
use Idimption\Entity\UserSubscription;
use Idimption\Exception\BadRequestException;
use Throwable;

class App
{
    use SingletonWithMockTrait;

    protected $_config;
    protected $_sessionId;
    protected $_startTime;
    protected $_params;
    /** @var Logger */
    protected $_log;

    protected function init()
    {
        $this->_config = require(__DIR__ . '/../config/config.php');
        $this->_sessionId = mt_rand();
        $this->_startTime = microtime(true);
        $this->_log = new Logger('app.log');
    }

    public function getConfig(...$fieldNames)
    {
        $config = $this->_config;
        foreach ($fieldNames as $fieldName) {
            $config = $config[$fieldName] ?? null;
        }
        return $config;
    }

    public function getSessionId()
    {
        return $this->_sessionId;
    }

    public function getLogPrefix()
    {
        $time = date('Y-m-d H:i:s');
        $dt = microtime(true) - $this->_startTime;
        return "[$this->_sessionId] [$time] [$dt]";
    }

    public function log($message)
    {
        $this->_log->log($message);
    }

    public function getFrontEndUri($uri = '')
    {
        return $this->getConfig('frontEndUri') . $uri;
    }

    public function getUri()
    {
        return PHP_SAPI === 'cli' ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
    }

    public function getParams()
    {
        if ($this->_params === null) {
            $this->_params = $_GET;
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $inputArray = json_decode($input, true);
                if (is_array($inputArray)) {
                    $this->_params = array_merge($this->_params, $inputArray);
                }
            }
        }
        return $this->_params;
    }

    public function getParam($name, $mandatory = false, $acceptedFormats = [])
    {
        $value = $this->getParams()[$name] ?? null;
        if ($mandatory && $value === null) {
            throw new BadRequestException('Missing ' . $name . ' parameter');
        }
        if ($acceptedFormats) {
            $acceptedFormats = (array)$acceptedFormats;
            if (!$mandatory) {
                $acceptedFormats[] = 'NULL';
            }
            if (!in_array(gettype($value), $acceptedFormats)) {
                throw new BadRequestException($name . ' parameter should be ' . implode(' or ', $acceptedFormats));
            }
        }
        return $value;
    }

    public function getStartTime()
    {
        return (int)$this->_startTime;
    }

    public function run($callback)
    {
        header('X-Api-Session-Id: ' . $this->_sessionId);
        try {
            $this->log('URL: ' . $this->getUri());
            $params = $this->getParams();
            $this->log('Params: ' . json_encode($params, JSON_PRETTY_PRINT));
            $result = call_user_func($callback);
            $error = null;
            $success = true;

            $this->sendNotifications();
        } catch (Throwable $exception) {
            $result = null;
            $error = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
            $success = false;
        } finally {
            $this->log('Response code: ' . ($success ? 200 : $error['code']));
        }

        header('Content-Type: text/json');
        echo json_encode(
            [
                'success' => $success,
                'result' => $result,
                'error' => $error,
                'sessionId' => $this->_sessionId,
            ],
            JSON_PRETTY_PRINT
        );
    }

    public function sendNotifications()
    {
        $allChanges = AllEntities::popAllChanges();

        $currentUser = Auth::getLoggedInUser();

        /** @var User $recipient */
        foreach (User::getInstance()->getAllRows() as $recipient) {
            if ($recipient->id === Auth::getLoggedInUserId()) {
                continue;
            }

            if (!$recipient->verifiedEmail) {
                continue;
            }

            if (!$recipient->subscribeToAll && $currentUser && UserSubscription::getInstance()->getUserSubscriptionForObject($recipient, $currentUser) === false) {
                continue;
            }

            foreach ($allChanges as $tableName => $tableChanges) {
                foreach ($tableChanges as $change) {
                    /** @var BaseEntity $row */
                    $row = $change->getInfoRow();

                    $reason = $recipient->subscribeToAll ? 'you are subscribed to all updates' : $row->getNotificationReason($change, $recipient);
                    if (!$reason) {
                        continue;
                    }

                    $changeHtml = $row->formatChange($change, $recipient);
                    if ($changeHtml || $change->action !== EntityUpdateAction::UPDATE) {
                        $subjectText = Auth::getLoggedInUserName() . ' ' . $row->getChangeSummary($change, $recipient, false);
                        $subjectHtml = htmlspecialchars(Auth::getLoggedInUserName()) . ' ' . $row->getChangeSummary($change, $recipient, true);
                        $html = "<h2>" . $subjectHtml . "</h2>\n";
                        $html .= $changeHtml;
                        $html .= "<p>You received this email because $reason. <a href='" . $recipient->getVerificationUrl('/profile') . "'>Manage subscription settings</a>.</p>\n";
                        Email::getInstance()->queue(
                            $subjectText,
                            Html::emailHeader() . $html . Html::emailFooter(),
                            [$recipient->email]
                        );
                    }
                }
            }
        }
    }
}
