<?php
/**
 * 定义 OK_Adapter_Mysql 类
 *
 * @package database
 */

/**
 * OK_Adapter_Mysql 提供了对 mysql 数据库的支持
 *
 * @package database
 */
class OK_Adapter_Cassandra extends OK_Adapter_Abstract
{


	
    protected $_bind_enabled = false;

    function __construct($dsn, $id)
    {
        if (! is_array($dsn))
        {
            $dsn = OK_DB::parseDSN($dsn);
        }
        parent::__construct($dsn, $id);
        $this->_schema = $dsn['keyspace'];
    }

    function connect()
    {
    	//! $pconnect = false, $force_new = false
    	
    	//if (is_resource($this->_conn)) { return; }
    	if (Cassandra_Conn::isConnected()) { return; }
    
    	/**
    	 * 为 cassandra 创建数据库链接
    	 */
    	
		Cassandra_Conn::add_node($this->_dsn['host'],$this->_dsn['port']);
		
    	return ;
    }
    
    /**
     * 确认是否已经连接到数据库
     *
     * @return boolean
     */
    function isConnected()
    {
    	return Cassandra_Conn::isConnected();
    }
    
    /**
     * 数据库连接句柄集合
     *
     * @var array
     */
    private $_conns = array();
    
    function getCF($columnFamily , $keyspace = null)
    {
		if (empty($this->_conns[$columnFamily])) {
			if (is_null($keyspace)) {
				$keyspace = $this->_dsn['keyspace'];
			}
			$this->_conns[$columnFamily] = new Cassandra_CF($keyspace,$columnFamily);
		}
    	return $this->_conns[$columnFamily];
    }
    
    /**
     * 更新数据库中的记录
     *
     * @param string $table_name 要操作的数据表
     * @param array|OK_Expr $row 要更新的数据
     */
    function update($table_name, $row, $where)
    {
    	
    	/* @var $conn Cassandra_CF */
    	$conn = $this->_conns[$table_name];
    	
    	//dprint($conn->get_range());
    	foreach ($row as $column => $value) {
    		$conn->set($where['id'] , $column , $value);
    	}
    	
    }
    

    /**
     * 删除指定数据表中符合条件的记录
     */
    function delete($table_name, $where)
    {
    	/* @var $conn Cassandra_CF */
    	$conn = $this->_conns[$table_name];
    	$conn->remove($where['id']);
    }
    
    
    

    function pconnect()
    {
        $this->connect(true);
    }

    function nconnect()
    {
        $this->connect(false, true);
    }

    /**
     * 关闭数据库连接
     *
     */
    function close()
    {
        if (Cassandra_Conn::isConnected())
        {
            Cassandra_Conn::close();
        }
        $this->_clear();
    }
    
    /**
     * 关闭数据库连接后清理资源
     */
    protected function _clear()
    {
        $this->_conns = null;
        $this->_last_err = null;
        $this->_last_err_code = null;
        $this->_insert_id = null;
        $this->_trans_count = 0;
    }
    

    function qstr($value)
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
        if (! ($value instanceof OK_Expr))
        {
            return "'" . mysql_real_escape_string($value, $this->_conn) . "'";
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
        catch (OK_DB_Exception $ex)
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
        return mysql_insert_id($this->_conn);
    }

    function affectedRows()
    {
        return mysql_affected_rows($this->_conn);
    }

    function execute($sql, $inputarr = null)
    {
        if (is_array($inputarr))
        {
            $sql = $this->_fakebind($sql, $inputarr);
        }

        if ($this->_log_enabled)
        {
            OK_Log::log('[DB EXECUTE] ' . str_replace("\n", ' ', $sql), OK_Log::DEBUG);
        }

        if (! $this->_conn)
        {
            $this->connect();
        }
        $result = mysql_query($sql, $this->_conn);

        if (is_resource($result))
        {
            return new OK_Result_Mysql($result, $this->_fetch_mode);
        }
        elseif ($result)
        {
            $this->_last_err = null;
            $this->_last_err_code = null;
            return $result;
        }
        else
        {
            $this->_last_err = mysql_error($this->_conn);
            $this->_last_err_code = mysql_errno($this->_conn);
            $this->_has_failed_query = true;

            if ($this->_last_err_code == 1062)
            {
                throw new OK_DB_Exception_DuplicateKey($sql, $this->_last_err, $this->_last_err_code);
            }
            else
            {
                throw new OK_DB_Exception($sql, $this->_last_err, $this->_last_err_code);
            }
        }
    }

    function selectLimit($sql, $offset = 0, $length = 30, array $inputarr = null)
    {
        if (! is_null($offset))
        {
            $sql .= ' LIMIT ' . (int) $offset;
            if (! is_null($length))
            {
                $sql .= ', ' . (int) $length;
            }
            else
            {
                $sql .= ', 18446744073709551615';
            }
        }
        elseif (! is_null($length))
        {
            $sql .= ' LIMIT ' . (int) $length;
        }
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
		/**
		 * 为 cassandra 构造 metaColumns
		 */
		return $retarr = array(
			'id' => array(
				'name' => 'id' , 
				'scale' => '',
				'type' => 'int',
				'length' => '11',
				'ptype' => 'autoincr',
				'not_null' => '1',
				'pk' => '1',
				'auto_incr' => '1',
				'binary' => '',
				'unsigned' => '',
				'default' => '',
				'has_default' => '',
				'desc' => '',
			),
			'value' => array(
				'name' => 'value' , 
				'scale' => '',
				'type' => 'int',
				'length' => '11',
				'ptype' => '',
				'not_null' => '1',
				'pk' => '',
				'auto_incr' => '1',
				'binary' => '',
				'unsigned' => '',
				'default' => '',
				'has_default' => '',
				'desc' => '',
			),
			
			'key_ranks' => array(
				'name' => 'key_ranks' , 
				'scale' => '',
				'type' => 'int',
				'length' => '11',
				'ptype' => '',
				'not_null' => '1',
				'pk' => '',
				'auto_incr' => '1',
				'binary' => '',
				'unsigned' => '',
				'default' => '',
				'has_default' => '',
				'desc' => '',
			),
		);
        

        $rs = $this->execute(sprintf('SHOW FULL COLUMNS FROM %s', $this->qid($table_name)));

        $retarr = array();
        $rs->fetch_mode = OK_DB::FETCH_MODE_ASSOC;
        $rs->result_field_name_lower = true;

        while (($row = $rs->fetchRow()))
        {
            $field = array();
            $field['name'] = $row['field'];
            $type = strtolower($row['type']);

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
            $field['not_null'] = (strtolower($row['null']) != 'yes');
            $field['pk'] = (strtolower($row['key']) == 'pri');
            $field['auto_incr'] = (strpos($row['extra'], 'auto_incr') !== false);
            if ($field['auto_incr'])
            {
                $field['ptype'] = 'autoincr';
            }
            $field['binary'] = (strpos($type, 'blob') !== false);
            $field['unsigned'] = (strpos($type, 'unsigned') !== false);

            $field['has_default'] = $field['default'] = null;
            if (! $field['binary'])
            {
                $d = $row['default'];
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
        //dprint($retarr);
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


