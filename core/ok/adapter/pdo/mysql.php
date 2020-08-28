<?php
/**
 * 定义 OK_Adapter_Pdo 类
 *
 * @package database
 */

/**
 * OK_Adapter_Pdo_Mysql 提供了对 mysql 数据库的支持
 *
 * @package database
 */
class OK_Adapter_Pdo_Mysql extends OK_Adapter_Abstract
{
	protected $_pdo_type = 'mysql';

    protected $_bind_enabled = false;

    function __construct($dsn, $id)
    {
        if (! is_array($dsn))
        {
            $dsn = OK_DB::parseDSN($dsn);
        }
        parent::__construct($dsn, $id);
        $this->_schema = $dsn['database'];
    }

    function connect($pconnect = false, $force_new = false)
    {
    	
        if (is_resource($this->_conn)) { return; }

        $this->_last_err = null;
        $this->_last_err_code = null;

        if (isset($this->_dsn['port']) && $this->_dsn['port'] != '')
        {
            $host = $this->_dsn['host'] . ':' . $this->_dsn['port'];
        }
        else
        {
            $host = $this->_dsn['host'];
        }

        if (! isset($this->_dsn['login']))
        {
            $this->_dsn['login'] = '';
        }

        if (! isset($this->_dsn['password']))
        {
            $this->_dsn['password'] = '';
        }
        //pecho($this->_dsn);
        //! 初始化
        //$this->_pdo_type = $this->_dsn['driver'];
        //
        $dsn = array();
        if (!empty($this->_dsn['database'])) { $dsn['dbname'] = $this->_dsn['database']; }
        if (!empty($this->_dsn['host'])) { $dsn['host'] = $this->_dsn['host']; }
        if (!empty($this->_dsn['port'])) { $dsn['port'] = $this->_dsn['port']; }

        $user = $this->_dsn['login'];
        $password = $this->_dsn['password'];

        $dsn_string = sprintf('%s:%s', $this->_pdo_type, http_build_query($dsn, '', ';'));
        

        try {
            $this->_conn = new PDO($dsn_string, $user, $password);
            
            $this->_conn->exec("set names ".$this->_dsn['charset']);
            
            
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
    
    
    function pconnect2()
    {
        $this->connect(true);
    }

    function nconnect2()
    {
        $this->connect(false, true);
    }

    function close2()
    {
        if (is_resource($this->_conn))
        {
            mysql_close($this->_conn);
        }
        parent::_clear();
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
    
    
    function qstr2($value)
    {
        if (is_array($value))
        {
            foreach ($value as $offset => $v)
            {
                $value[$offset] = $this->qstr($v);
            }
            return $value;
        }
        if (is_int($value)) { return $value; }
        if (is_bool($value))
        {
            return $value ? $this->_true_value : $this->_false_value;
        }
        if (is_null($value))
        {
            return $this->_null_value;
        }
        //pecho($value);
        if (! ($value instanceof OK_Expr))
        {
            //return "'" . mysql_real_escape_string($value, $this->_conn) . "'";
            return "'" . mysql_real_escape_string($value) . "'";
        }
        return $value->formatToString($this);
    }

    function identifier($name)
    {
        return ($name != '*') ? "`{$name}`" : '*';
    }

    function nextID($table_name, $field_name, $start_value = 1)
    {
        $seq_table_name = $this->qid("{$table_name}_{$field_name}_seq");
        $next_sql = sprintf('UPDATE %s SET id = LAST_INSERT_ID(id + 1)', $seq_table_name);
        $start_value = intval($start_value);

        $successed = false;
        try
        {
            // 首先产生下一个序列值
            $this->execute($next_sql);
            if ($this->affectedRows() > 0)
            {
                $successed = true;
            }
        }
        catch (OK_Exception $ex)
        {
            // 产生序列值失败，创建序列表
            $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seq_table_name));
        }

        if (! $successed)
        {
            // 没有更新任何记录或者新创建序列表，都需要插入初始的记录
            if ($this->getOne(sprintf('SELECT COUNT(*) FROM %s', $seq_table_name)) == 0)
            {
                $sql = sprintf('INSERT INTO %s VALUES (%s)', $seq_table_name, $start_value);
                $this->execute($sql);
            }
            $this->execute($next_sql);
        }
        // 获得新的序列值
        $this->_insert_id = $this->insertID();
        return $this->_insert_id;
    }

    function createSeq($seq_name, $start_value = 1)
    {
        $seq_table_name = $this->qid($seq_name);
        $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seq_table_name));
        $this->execute(sprintf('INSERT INTO %s VALUES (%s)', $seq_table_name, $start_value));
    }

    function dropSeq($seq_name)
    {
        $this->execute(sprintf('DROP TABLE %s', $this->qid($seq_name)));
    }

    function insertID()
    {
        return $this->_conn->lastInsertId();
    }
    
    public function affectedRows() {
        return $this->_lastrs instanceof PDOStatement ? $this->_lastrs->rowCount() : 0;
    }

    public function execute($sql, $inputarr = null)
    {
    	//pecho('hahaha');
    	
        if (!$this->isConnected()) { $this->connect(); }

        //pecho($sql , false);
        $sth = $this->_conn->prepare($sql);
        if ($this->_log_enabled) { OK_Log::log($sql, OK_Log::DEBUG); }

        $result = $sth->execute((array)$inputarr);
        if (false === $result) {
            $error = $sth->errorInfo();
            $this->_last_err = $error[2];
            $this->_last_err_code = $error[0];
            $this->_has_failed_query = true;

            throw new OK_Exception($sql, $this->_last_err, $this->_last_err_code);
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
        return $this->execute($sql, $inputarr);
    }

    function startTrans()
    {
        if (! $this->_transaction_enabled)
        {
            return false;
        }
        if ($this->_trans_count == 0)
        {
            $this->execute('START TRANSACTION');
            $this->_has_failed_query = false;
        }
        elseif ($this->_trans_count && $this->_savepoint_enabled)
        {
            $savepoint = 'savepoint_' . $this->_trans_count;
            $this->execute("SAVEPOINT `{$savepoint}`");
            array_push($this->_savepoints_stack, $savepoint);
        }
        ++ $this->_trans_count;
        return true;
    }

    function completeTrans($commit_on_no_errors = true)
    {
        if ($this->_trans_count == 0)
        {
            return;
        }
        -- $this->_trans_count;
        if ($this->_trans_count == 0)
        {
            if ($this->_has_failed_query == false && $commit_on_no_errors)
            {
                $this->execute('COMMIT');
            }
            else
            {
                $this->execute('ROLLBACK');
            }
        }
        elseif ($this->_savepoint_enabled)
        {
            $savepoint = array_pop($this->_savepoints_stack);
            if ($this->_has_failed_query || $commit_on_no_errors == false)
            {
                $this->execute("ROLLBACK TO SAVEPOINT `{$savepoint}`");
            }
        }
    }

    function metaColumns($table_name)
    {
        static $type_mapping = array(
            'bit'           => 'int1',
            'tinyint'       => 'int1',
            'bool'          => 'bool',
            'boolean'       => 'bool',
            'smallint'      => 'int2',
            'mediumint'     => 'int3',
            'int'           => 'int4',
            'integer'       => 'int4',
            'bigint'        => 'int8',
            'float'         => 'float',
            'double'        => 'double',
            'doubleprecision' => 'double',
            'float unsigned' => 'float',
            'decimal'       => 'dec',
            'dec'           => 'dec',

            'date'          => 'date',
            'datetime'      => 'datetime',
            'timestamp'     => 'timestamp',
            'time'          => 'time',
            'year'          => 'int2',

            'char'          => 'char',
            'nchar'         => 'char',
            'varchar'       => 'varchar',
            'nvarchar'      => 'varchar',
            'binary'        => 'binary',
            'varbinary'     => 'varbinary',
            'tinyblob'      => 'blob',
            'tinytext'      => 'text',
            'blob'          => 'blob',
            'text'          => 'text',
            'mediumblob'    => 'blob',
            'mediumtext'    => 'text',
            'longblob'      => 'blob',
            'longtext'      => 'text',
            'enum'          => 'enum',
            'set'           => 'set'
        );
        
        //$rs = $this->_conn->query(sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name)));
		$sql = sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name));
		//pecho($sql , false);
		//pecho($sql);
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute();
		while ($row = $stmt->fetch())
		{
			//pecho($row);
/*
			print_r($row);
		}
        
        
        exit;
		//pecho(sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name)));
		
		//$rs = $this->_conn->query(sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name)));
		//foreach ($rs as $row) {
		//	print_r($row);
		//}
		//pecho($rs);
		$rs = $this->execute(sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name)));
//pecho($rs);
while ($row = $rs->fetch()) {
    pecho($row);
}
		
        $retarr = array();
        $rs->fetch_mode = OK_DB::FETCH_MODE_ASSOC;
        $rs->result_field_name_lower = true;

        //while (($row = $rs->fetchRow()))
        while (($row = $rs))
        {
*/
        	//pecho($row);
            $field = array();
            $field['name'] = $row['Field'];
            $type = strtolower($row['Type']);

            $field['scale'] = null;
            $query_arr = false;
            if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_arr))
            {
                $field['type'] = $query_arr[1];
                $field['length'] = is_numeric($query_arr[2]) ? $query_arr[2] : - 1;
                $field['scale'] = is_numeric($query_arr[3]) ? $query_arr[3] : - 1;
            }
            elseif (preg_match('/^(.+)\((\d+)/', $type, $query_arr))
            {
                $field['type'] = $query_arr[1];
                $field['length'] = is_numeric($query_arr[2]) ? $query_arr[2] : - 1;
            }
            elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $query_arr))
            {
                $field['type'] = $query_arr[1];
                $arr = explode(",", $query_arr[2]);
                $field['enums'] = $arr;
                $zlen = max(array_map("strlen", $arr)) - 2; // PHP >= 4.0.6
                $field['length'] = ($zlen > 0) ? $zlen : 1;
            }
            else
            {
                $field['type'] = $type;
                $field['length'] = - 1;
            }

            $field['ptype'] = $type_mapping[strtolower($field['type'])];
            $field['not_null'] = (strtolower($row['Null']) != 'yes');
            $field['pk'] = (strtolower($row['Key']) == 'pri');
            $field['auto_incr'] = (strpos($row['Extra'], 'auto_incr') !== false);
            if ($field['auto_incr'])
            {
                $field['ptype'] = 'autoincr';
            }
            $field['binary'] = (strpos($type, 'blob') !== false);
            $field['unsigned'] = (strpos($type, 'unsigned') !== false);

            $field['has_default'] = $field['default'] = null;
            if (! $field['binary'])
            {
                $d = $row['Default'];
                if (!is_null($d) && strtolower($d) != 'null')
                {
                    $field['has_default'] = true;
                    $field['default'] = $d;
                }
            }

            if ($field['type'] == 'tinyint' && $field['length'] == 1)
            {
                $field['ptype'] = 'bool';
            }

            $field['desc'] = ! empty($row['comment']) ? $row['comment'] : '';
            if (! is_null($field['default']))
            {
                switch ($field['ptype'])
                {
                case 'int1':
                case 'int2':
                case 'int3':
                case 'int4':
                    $field['default'] = intval($field['default']);
                    break;
                case 'float':
                case 'double':
                case 'dec':
                    $field['default'] = doubleval($field['default']);
                    break;
                case 'bool':
                    $field['default'] = (bool) $field['default'];
                }
            }

            $retarr[strtolower($field['name'])] = $field;
        }
        //pecho($retarr);
        return $retarr;
    }

    function metaTables($pattern = null, $schema = null)
    {
        $sql = 'SHOW TABLES';
        if ($schema != '')
        {
            $sql .= " FROM `{$schema}`";
        }
        if ($pattern != '')
        {
            $sql .= ' LIKE ' . $this->qstr($pattern);
        }
        return $this->getCol($sql);
    }

    protected function _fakebind($sql, $inputarr)
    {
        $arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($inputarr as $value)
        {
            if (isset($arr[0]))
            {
                $sql .= $this->qstr($value) . array_shift($arr);
            }
        }
        return $sql;
    }
    
    
}

class OK_Adapter_Pdo_Exception extends OK_Exception {
}
