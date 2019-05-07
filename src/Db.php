<?php

namespace Idimption;

use Idimption\Entity\AllEntities;
use Idimption\Exception\DbException;
use mysqli;
use mysqli_result;
use Throwable;

class Db
{
    use SingletonWithMockTrait;

    /** @var mysqli */
    protected $_mysqli;

    /** @var int */
    protected $_insertedId = 0;

    /** @var Logger */
    protected $_log;

    protected function init()
    {
        $config = App::getInstance()->getConfig('db');
        $this->_log = new Logger('db.log');
        $this->_mysqli = mysqli_init();
        $this->_mysqli->connect(
            $config['host'] ?? 'localhost',
            $config['user'],
            $config['pass'],
            $config['name'] ?? 'idimption',
            $config['port'] ?? null
        );
    }

    protected function dispose()
    {
        $this->_mysqli->close();
    }

    private function _log($message)
    {
        $this->_log->log($message);
    }

    /**
     * @param string|null $message
     * @param int|null $code
     * @return DbException
     */
    private function _exception($message = null, $code = null)
    {
        $message = $message ?? $this->_mysqli->error;
        $code = $code ?? $this->_mysqli->errno;
        $this->_log("Error: $message");
        return new DbException($message, $code);
    }

    public function escapeValue($value)
    {
        if ($value === null) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }
        return "'" . $this->_mysqli->real_escape_string($value) . "'";
    }

    public function escapeName($name)
    {
        return "`$name`";
    }

    /**
     * @param string $sql
     * @param bool $log
     * @return mysqli_result|bool
     * @throws DbException
     */
    private function _query($sql, $log)
    {
        if ($log) {
            $this->_log("SQL: $sql;");
        }
        $startTime = microtime(true);
        $result = $this->_mysqli->query($sql);
        $dt = microtime(true) - $startTime;
        if ($log) {
            $this->_log("Query took $dt seconds");
        }
        if ($result === false) {
            throw $this->_exception();
        }
        if ($log) {
            $this->_log('Affected rows: ' . $this->getAffectedRowsCount());
        }
        if ($log) {
            $insertedId = $this->getInsertedId();
            if ($insertedId) {
                $this->_log("ID: $insertedId");
            }
        }
        return $result;
    }

    /**
     * @param string $sql
     * @param bool $assoc
     * @return array[]
     * @throws DbException
     */
    public function select($sql, $assoc = true)
    {
        $result = $this->_query($sql, false);
        if ($result === true) {
            throw $this->_exception('Asserted SELECT query, got UPDATE query', -1);
        }
        $rows = $result->fetch_all($assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
        $result->close();
        return $rows;
    }

    /**
     * @param string $tableName
     * @return array[]
     * @throws DbException
     */
    public function selectAll($tableName)
    {
        /** @noinspection SqlResolve */
        return $this->select("
            SELECT *
            FROM " . $this->escapeName($tableName) . "
            ORDER BY id
        ");
    }

    /**
     * @param string $sql
     * @param bool $assoc
     * @return array|null
     * @throws DbException
     */
    public function selectRow($sql, $assoc = true)
    {
        return $this->select($sql, $assoc)[0] ?? null;
    }

    /**
     * Just executes the query, without any additional hooks
     *
     * @param string $sql
     * @throws DbException
     */
    private function _internalExec($sql)
    {
        $result = $this->_query($sql, true);
        if ($result !== true) {
            $result->close();
        }
    }

    /**
     * @param string $sql
     * @param array|null $logData
     * @throws DbException
     */
    public function exec($sql, $logData = null)
    {
        $this->_internalExec($sql);
        $type = strtoupper(preg_split('~\s~', trim($sql), 2)[0]);
        if ($type === 'INSERT') {
            $this->_insertedId = $this->_mysqli->insert_id;
        }

        if ($logData !== null) {
            $logData['type'] = $type;
            $logData['sql'] = $sql;
            if ($type === 'INSERT') {
                $logData['insertedId'] = $this->_insertedId;
            }
            $logData['affectedRows'] = $this->getAffectedRowsCount();
            $this->dbLog('DB', $logData);
        }
    }

    public function insertRow($tableName, $data, $log = true)
    {
        $fieldNameSqlParts = [];
        $fieldValueSqlParts = [];

        foreach ($data as $fieldName => $fieldValue) {
            $fieldNameSqlParts[] = $this->escapeName($fieldName);
            $fieldValueSqlParts[] = $this->escapeValue($fieldValue);
        }

        $this->exec(
            '
                INSERT INTO ' . $this->escapeName($tableName) . '
                (' . implode(', ', $fieldNameSqlParts) . ')
                VALUES (' . implode(', ', $fieldValueSqlParts) . ')
            ',
            $log
                ? [
                    'tableName' => $tableName,
                    'data' => $data,
                ]
                : null
        );
    }

    public function updateRow($tableName, $id, $data, $log = true)
    {
        $updateSqlParts = [];

        foreach ($data as $fieldName => $fieldValue) {
            $updateSqlParts[] = $this->escapeName($fieldName) . ' = ' . $this->escapeValue($fieldValue);
        }

        if ($updateSqlParts) {
            /** @noinspection SqlResolve */
            $this->exec(
                '
                    UPDATE ' . $this->escapeName($tableName) . '
                    SET ' . implode(', ', $updateSqlParts) . '
                    WHERE id = ' . $this->escapeValue($id) . '
                ',
                $log
                    ? [
                        'tableName' => $tableName,
                        'updates' => $data,
                        'id' => $id,
                    ]
                    : null
            );
        }
    }

    public function deleteRow($tableName, $id, $log = true)
    {
        /** @noinspection SqlResolve */
        $this->exec(
            '
                DELETE
                FROM ' . $this->escapeName($tableName) . '
                WHERE id = ' . $this->escapeValue($id) . '
            ',
            $log
                ? [
                    'tableName' => $tableName,
                    'id' => $id,
                ]
                : null
        );
    }

    /**
     * @return int|null
     */
    public function getInsertedId()
    {
        return $this->_insertedId;
    }

    /**
     * @return int
     */
    public function getAffectedRowsCount()
    {
        return $this->_mysqli->affected_rows;
    }

    public function dbLog($type, $data)
    {
        if (!is_array($data)) {
            $data = ['data' => $data];
        }
        $app = App::getInstance();
        $this->_internalExec('
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
              ' . App::getInstance()->getStartTime() . ',
              ' . $this->escapeValue($app->getSessionId()) . ',
              ' . $this->escapeValue($app->getUri()) . ',
              ' . $this->escapeValue($app->getParams()) . ',
              ' . $this->escapeValue(Auth::getLoggedInUserId()) . ',
              ' . $this->escapeValue($type) . ',
              ' . $this->escapeValue($data) . '
            )
        ');
    }

    /**
     * Executes a function inside of SQL transaction.
     *
     * @param callable $callback The function to execute
     * @return mixed The result of the function
     */
    public function transaction($callback)
    {
        $changes = AllEntities::getAllChanges();
        $this->_mysqli->begin_transaction();
        $this->_mysqli->autocommit(false);
        $result = null;
        try {
            $result = call_user_func($callback);
        } catch (Throwable $exception) {
            // Revert changes array to the previous state
            AllEntities::setAllChanges($changes);
            $this->_mysqli->rollback();
            throw $exception;
        } finally {
            $this->_mysqli->autocommit(true);
        }
        if (!$this->_mysqli->commit()) {
            throw $this->_exception();
        }
        return $result;
    }
}
