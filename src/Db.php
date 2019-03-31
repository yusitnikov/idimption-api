<?php

namespace Idimption;

use Idimption\Exception\DbException;

class Db
{
    /**
     * @return self
     */
    private static function getInstance()
    {
        static $instance = null;
        return $instance = $instance ?? new self();
    }

    /** @var \mysqli */
    private $_mysqli;

    /** @var int */
    private $_insertedId = 0;

    /** @var resource */
    private $_log;

    private function __construct()
    {
        $config = App::getInstance()->getConfig('db');
        $this->_log = fopen(__DIR__ . '/../logs/db.log', 'ab');
        $this->_mysqli = mysqli_init();
        $this->_mysqli->connect(
            $config['host'] ?? 'localhost',
            $config['user'],
            $config['pass'],
            $config['name'] ?? 'idimption',
            $config['port'] ?? null
        );
    }

    function __destruct()
    {
        fclose($this->_log);
        $this->_mysqli->close();
    }

    private static function _log($message)
    {
        $prefix = App::getInstance()->getLogPrefix();
        fwrite(self::getInstance()->_log, "$prefix $message\n");
    }

    /**
     * @param string|null $message
     * @param int|null $code
     * @return DbException
     */
    private static function _exception($message = null, $code = null)
    {
        $message = $message ?? self::_mysqli()->error;
        $code = $code ?? self::_mysqli()->errno;
        self::_log("Error: $message");
        return new DbException($message, $code);
    }

    /**
     * @return \mysqli
     */
    private static function _mysqli()
    {
        return self::getInstance()->_mysqli;
    }

    public static function escapeValue($value)
    {
        if ($value === null) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }
        return "'" . self::_mysqli()->real_escape_string($value) . "'";
    }

    public static function escapeName($name)
    {
        return "`$name`";
    }

    /**
     * @param string $sql
     * @return \mysqli_result|bool
     * @throws DbException
     */
    private static function _query($sql)
    {
        self::_log("SQL: $sql;");
        $startTime = microtime(true);
        $result = self::_mysqli()->query($sql);
        $dt = microtime(true) - $startTime;
        self::_log("Query took $dt seconds");
        if ($result === false) {
            throw self::_exception();
        }
        self::_log('Affected rows: ' . self::getAffectedRowsCount());
        $insertedId = self::getInsertedId();
        if ($insertedId) {
            self::_log("ID: $insertedId");
        }
        return $result;
    }

    /**
     * @param string $sql
     * @param bool $assoc
     * @return array[]
     * @throws DbException
     */
    public static function select($sql, $assoc = true)
    {
        $result = self::_query($sql);
        if ($result === true) {
            throw self::_exception('Asserted SELECT query, got UPDATE query', -1);
        }
        $rows = $result->fetch_all($assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
        self::_log('Returned rows: ' . count($rows));
        $result->close();
        return $rows;
    }

    /**
     * Just executes the query, without any additional hooks
     *
     * @param string $sql
     * @throws DbException
     */
    private static function _internalExec($sql)
    {
        $result = self::_query($sql);
        if ($result !== true) {
            $result->close();
        }
    }

    /**
     * @param string $sql
     * @param array $logData
     * @throws DbException
     */
    public static function exec($sql, $logData = [])
    {
        self::_internalExec($sql);
        $type = strtoupper(preg_split('~\s~', trim($sql), 2)[0]);
        if ($type === 'INSERT') {
            self::getInstance()->_insertedId = self::_mysqli()->insert_id;
        }

        $logData['type'] = $type;
        $logData['sql'] = $sql;
        if ($type === 'INSERT') {
            $logData['insertedId'] = self::getInstance()->_insertedId;
        }
        $logData['affectedRows'] = self::getAffectedRowsCount();
        self::dbLog('DB', $logData);
    }

    public static function insertRow($tableName, $data)
    {
        $fieldNameSqlParts = [];
        $fieldValueSqlParts = [];

        foreach ($data as $fieldName => $fieldValue) {
            $fieldNameSqlParts[] = self::escapeName($fieldName);
            $fieldValueSqlParts[] = self::escapeValue($fieldValue);
        }

        self::exec('
            INSERT INTO ' . self::escapeName($tableName) . '
            (' . implode(', ', $fieldNameSqlParts) . ')
            VALUES (' . implode(', ', $fieldValueSqlParts) . ')
        ', [
            'tableName' => $tableName,
            'data' => $data,
        ]);
    }

    public static function updateRow($tableName, $id, $data)
    {
        $updateSqlParts = [];

        foreach ($data as $fieldName => $fieldValue) {
            $updateSqlParts[] = self::escapeName($fieldName) . ' = ' . self::escapeValue($fieldValue);
        }

        if ($updateSqlParts) {
            /** @noinspection SqlResolve */
            self::exec('
                UPDATE ' . self::escapeName($tableName) . '
                SET ' . implode(', ', $updateSqlParts) . '
                WHERE id = ' . self::escapeValue($id) . '
            ', [
                'tableName' => $tableName,
                'updates' => $data,
                'id' => $id,
            ]);
        }
    }

    public static function deleteRow($tableName, $id)
    {
        /** @noinspection SqlResolve */
        self::exec('
          DELETE
          FROM ' . self::escapeName($tableName) . '
          WHERE id = ' . self::escapeValue($id) . '
        ', [
            'tableName' => $tableName,
            'id' => $id,
        ]);
    }

    /**
     * @return int|null
     */
    public static function getInsertedId()
    {
        return self::getInstance()->_insertedId;
    }

    /**
     * @return int
     */
    public static function getAffectedRowsCount()
    {
        return self::_mysqli()->affected_rows;
    }

    public static function dbLog($type, $data)
    {
        if (!is_array($data)) {
            $data = ['data' => $data];
        }
        $app = App::getInstance();
        self::_internalExec('
            INSERT INTO log
            (
              createdAt,
              requestId,
              requestUri,
              requestParams,
              userId,
              type,
              data
            )
            VALUES(
              ' . time() . ',
              ' . self::escapeValue($app->getSessionId()) . ',
              ' . self::escapeValue($app->getUri()) . ',
              ' . self::escapeValue($app->getParams()) . ',
              ' . self::escapeValue(Auth::getLoggedInUserId()) . ',
              ' . self::escapeValue($type) . ',
              ' . self::escapeValue($data) . '
            )
        ');
    }

    /**
     * Executes a function inside of SQL transaction.
     *
     * @param callable $callback The function to execute
     * @return mixed The result of the function
     */
    public static function transaction($callback)
    {
        self::_mysqli()->begin_transaction();
        self::_mysqli()->autocommit(false);
        $result = null;
        try {
            $result = call_user_func($callback);
        } catch (\Throwable $exception) {
            self::_mysqli()->rollback();
            throw $exception;
        } finally {
            self::_mysqli()->autocommit(true);
        }
        if (!self::_mysqli()->commit()) {
            throw self::_exception();
        }
        return $result;
    }
}
