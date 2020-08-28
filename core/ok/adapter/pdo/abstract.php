<?php
/**
 * 定义 OK_Adapter_Pdo_Abstract 类
 *
 * @package database
 */

/**
 * OK_Adapter_Pdo_Abstract 类是所有 PDO 驱动的基础类
 *
 * @package database
 */
abstract class OK_Adapter_Pdo_Abstract extends OK_Adapter_Abstract
{
    protected $_bind_enabled = false;

    public function __construct($dsn, $id) {
        if (!is_array($dsn)) { $dsn = OK_DB::parseDSN($dsn); }

        parent::__construct($dsn, $id);
    }

    public function connect($pconnect = false, $force_new = false) {
        if (!$force_new && $this->isConnected()) { return; }

        $dsn = array();
        if (!empty($this->_dsn['database'])) { $dsn['dbname'] = $this->_dsn['database']; }
        if (!empty($this->_dsn['host'])) { $dsn['host'] = $this->_dsn['host']; }
        if (!empty($this->_dsn['port'])) { $dsn['port'] = $this->_dsn['port']; }

        $user = $this->_dsn['login'];
        $password = $this->_dsn['password'];

        $dsn_string = sprintf('%s:%s', $this->_pdo_type, http_build_query($dsn, '', ';'));

        try {
            $this->_conn = new PDO($dsn_string, $user, $password);
        } catch (PDOException $e) { throw $e; }
    }

    public function close() {
        parent::_clear();
    }

    public function pconnect() {
        $this->connect();
    }

    public function nconnect() {
        $this->connect(false, true);
    }

    public function isConnected() {
        return $this->_conn instanceof PDO;
    }

    public function qstr($value) {
        if (is_array($value))
        {
            foreach ($value as $offset => $v)
            {
                $value[$offset] = $this->qstr($v);
            }
            return $value;
        }
        if (is_int($value) || is_float($value)) { return $value; }
        if (is_bool($value)) { return $value ? $this->_true_value : $this->_false_value; }
        if (is_null($value)) { return $this->_null_value; }

        if (!$this->isConnected()) { $this->connect(); }
        return $this->_conn->quote($value);
    }

    public function affectedRows() {
        return $this->_lastrs instanceof PDOStatement ? $this->_lastrs->rowCount() : 0;
    }

    public function execute($sql, $inputarr = null) {
        if (!$this->isConnected()) { $this->connect(); }

        $sth = $this->_conn->prepare($sql);
        if ($this->_log_enabled) { OK_Log::log($sql, OK_Log::DEBUG); }

        $result = $sth->execute((array)$inputarr);
        if (false === $result) {
            $error = $sth->errorInfo();
            $this->_last_err = $error[2];
            $this->_last_err_code = $error[0];
            $this->_has_failed_query = true;

            throw new OK_DB_Exception($sql, $this->_last_err, $this->_last_err_code);
        }

        $this->_lastrs = $sth;
        if ('select' == strtolower(substr($sql, 0, 6))) {
            return new OK_Result_Pdo($this->_lastrs, $this->_fetch_mode);
        } else {
            return $this->affectedRows();
        }
    }

    function selectLimit($sql, $offset = 0, $length = 30, array $inputarr = null)
    {
        $sql = sprintf('%s LIMIT %d OFFSET %d', $sql, $length, $offset);
        return $this->_execute($sql, $inputarr);
    }
}

/**
 * OK_Adapter_Pdo_Exception 异常封装所有 PDO 操作错误
 *
 * @package database
 */
class OK_Adapter_Pdo_Exception extends OK_Exception {
}

