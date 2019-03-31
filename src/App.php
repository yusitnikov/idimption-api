<?php

namespace Idimption;

use Idimption\Exception\BadRequestException;

class App
{
    private $_config;
    private $_sessionId;
    private $_startTime;
    private $_log;

    public static function getInstance()
    {
        static $instance = null;
        return $instance = $instance ?: new self();
    }

    private function __construct()
    {
        $this->_config = require(__DIR__ . '/../config/config.php');
        $this->_sessionId = mt_rand();
        $this->_startTime = microtime(true);
        $this->_log = fopen(__DIR__ . '/../logs/app.log', 'ab');
    }

    public function __destruct()
    {
        fclose($this->_log);
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
        $prefix = $this->getLogPrefix();
        fwrite($this->_log, "$prefix $message\n");
    }

    public function getFrontEndUri($uri = '')
    {
        return $this->getConfig('frontEndUri') . $uri;
    }

    public function getUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function getParams()
    {
        static $params = null;
        if ($params === null) {
            $params = $_GET;
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $inputArray = json_decode($input, true);
                if (is_array($inputArray)) {
                    $params = array_merge($params, $inputArray);
                }
            }
        }
        return $params;
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
        } catch (\Throwable $exception) {
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
}
