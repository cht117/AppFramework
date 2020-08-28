<?php
/**
 * OK_Adapter_Mssql 提供了对 mssql 数据库的支持
 * 
 */
class OK_Adapter_Mssql extends OK_Adapter_Abstract {
	
    protected $_bind_enabled = false;

    /**
     * 用于 genSeq()、dropSeq() 和 nextId() 的 SQL 查询语句
     */
    protected $NEXT_ID_SQL    = 'UPDATE %s WITH (TABLOCK,HOLDLOCK) SET id = id + 1';
    protected $CREATE_SEQ_SQL = 'CREATE TABLE %s (id float(53))';
    protected $INIT_SEQ_SQL   = 'INSERT INTO %s WITH (TABLOCK,HOLDLOCK) VALUES (%s)';
    protected $DROP_SEQ_SQL   = 'DROP TABLE %s';
    /**
     * 用于获取元数据的 SQL 查询语句
     */
    protected $META_COLUMNS_SQL = "SELECT a.COLUMN_NAME AS Field
			,(CASE WHEN CHARACTER_MAXIMUM_LENGTH IS NULL THEN DATA_TYPE + '(' + CONVERT(VARCHAR,NUMERIC_PRECISION) + ')' 
			ELSE DATA_TYPE + '(' + CONVERT(VARCHAR,CHARACTER_MAXIMUM_LENGTH) + ')' END) AS DataType
			,IS_NULLABLE AS Nullable
			,CONSTRAINT_TYPE AS PrimaryKey
			,COLUMN_DEFAULT AS DefaultValue
			,(CASE WHEN COLUMNPROPERTY(OBJECT_ID('%s'),a.COLUMN_NAME,'IsIdentity')=1 THEN 'auto_increment' ELSE '' END) AS Extra 
		FROM INFORMATION_SCHEMA.COLUMNS AS a LEFT JOIN (INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS b
			INNER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS c ON b.CONSTRAINT_NAME=c.CONSTRAINT_NAME  AND b.TABLE_NAME=c.TABLE_NAME) 
			ON a.COLUMN_NAME=b.COLUMN_NAME AND a.TABLE_NAME=b.TABLE_NAME
		WHERE  a.TABLE_NAME='%s'";
    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    protected $TRUE_VALUE  = 1;
    protected $FALSE_VALUE = 0;
    protected $NULL_VALUE = 'NULL';
    
    function __construct($dsn, $id)
    {
        if (! is_array($dsn)) { $dsn = OK_DB::parseDSN($dsn); }
        parent::__construct($dsn, $id);
        $this->_schema = $dsn['database'];
    }
    
    
    /**
     * 连接数据库，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    function connect($pconnect = false, $force_new = false)
    {
            if (is_resource($this->_conn)) { return; }

        $this->_last_err = null;
        $this->_last_err_code = null;
        //处理数据库连接信息
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
        //连接到MSSQL
        if ($pconnect)
        {
            $this->_conn = mssql_pconnect($host, $this->_dsn['login'], $this->_dsn['password'], $force_new);
        }
        else
        {
            $this->_conn = mssql_connect($host, $this->_dsn['login'], $this->_dsn['password'], $force_new);
        }

        if (! is_resource($this->_conn))
        {
            throw new OK_Exception('CONNECT DATABASE', mssql_get_last_message(), mssql_get_last_message());
        }
		//选择数据库
        if (! empty($this->_dsn['database']))
        {
        	if (!mssql_select_db($this->_dsn['database'], $this->_conn)) {
        		throw new OK_Exception('SELECT DATABASE', mssql_get_last_message(), mssql_get_last_message());
        	}
        }
    }
    
    /**
     * 创建一个持久连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    function pconnect()
    {
        $this->_connect(true);
    }
    
    /**
     * 强制创建一个新连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    function nconnect()
    {
        $this->_connect(false, true);
    }
    
    /**
     * 关闭数据库连接
     */
    function close()
    {
        if (is_resource($this->_conn))
        {
            mssql_close($this->_conn);
        }
        parent::_clear();
    }
    
    /**
     * 转义值
     *
     * 为了能够在 SQL 语句中安全的插入数据，应该用 qstr() 方法将数据中的特殊字符转义。
     *
     * example:
     * @code
     * $param = "It's live";
     * $param = $dbo->qstr($param);
     * $sql = "INSERT INTO posts (title) VALUES ({$param})";
     * $dbo->execute($sql);
     * @endcode
     *
     * 但更有效，而且更简单的方式是使用参数占位符：
     *
     * example:
     * @code
     * $param = "It's live";
     * $sql = "INSERT INTO posts (title) VALUES (?)";
     * $dbo->execute($sql, array($param));
     * @endcode
     *
     * 而且对于 Oracle 等数据库，由于限制每条 SQL 语句不能超过 4000 字节，
     * 因此在插入包含大量数据的记录时，必须使用参数占位符的形式。
     *
     * example:
     * @code
     * $title = isset($POST['title']) ? $POST['title'] : null;
     * $body = isset($POST['body']) ? $POST['body'] : null;
     *
     * ... 检查 $title、$body 是否为空 ...
     *
     * $sql = "INSERT INTO posts (title, body) VALUES (:title, :body)";
     * $dbo->execute($sql, array('title' => $title, 'body' => $body));
     * @endcode
     *
     * @param mixed $value
     *
     * @return string
     */
    function qstr($value)
    {
        if (is_bool($value)) { return $value ? $this->TRUE_VALUE : $this->FALSE_VALUE; }
        if (is_null($value)) { return $this->NULL_VALUE; }
        return "'" . $this->mssql_real_escape_string($value, $this->conn) . "'";
    }
    /**
     * 获得完全限定名
     *
     * @param string $name
     * @param string $alias
     * @param string $as
     *
     * @return string
     */
    function qid($name, $alias = null, $as = null)
    {
    	$arr=explode('.',$name);
    	if($this->_dsn['database']==$arr[0]){
	    	switch (count($arr)) {
	    		case 2:
	    			$name = $arr[0].'..'.$arr[1];
	    		break;
	    		case 3:
	    			$name =  $arr[0].'..'.$arr[1].'.'.$arr[2];
	    		break;
	    		default:
	    			$name = $name;
	    		break;
	    	}
    	}
    	if ($alias) {
    		return "{$name} {$as} " . $this->identifier($alias);
    	}
    	return $name;
    	
    }
    /**
     * 获得一个名字的规范名
     *
     * @param string $name
     *
     * @return string
     */
    function identifier($name)
    {
    	return ($name != '*') ? "{$name}" : '*';
    }
    
    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * 调用 nextID() 方法，将获得指定名称序列的下一个值。
     * 此处所指的序列，是指一个不断增大的数字。
     *
     * 假设本次调用 nextID() 返回 3，那么下一次调用 nextID() 就会返回一个比 3 更大的值。
     * nextID() 返回的序列值，可以作为记录的主键字段值，以便确保插入记录时总是使用不同的主键值。
     *
     * 可以使用多个序列，只需要指定不同的 $seq_name 参数即可。
     *
     * 在不同的数据库中，序列的产生方式各有不同。
     * PostgreSQL、Oracle 等数据库中，会使用数据库自带的序列功能来实现。
     * 其他部分数据库会创建一个后缀为 _seq 表来存放序列值。
     *
     * 例如 $seq_name 为 posts，则存放该序列的表名称为 posts_seq。
     *
     * @param string $table_name
     * @param string $field_name
     * @param string $start_value
     *
     * @return int
     */
    function nextID($table_name, $field_name, $start_value = 1)
    {
    	$arr=explode('.',$table_name);
    	if (count($arr)==2){
    		$seq_table_name=$arr[1];
    	}else{
    		$seq_table_name=$table_name;
    	}
		$num = $this->execute(sprintf('SELECT IDENT_CURRENT(\'%s\')+1 as nextid', $seq_table_name))->fetchOne();
		return $num;
    }
    
    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * 调用 nextID() 时，如果指定的序列不存在，则会自动调用 create_seq() 创建。
     * 开发者也可以自行调用 create_seq() 创建一个新序列。
     *
     * @param string $seq_name
     * @param int $start_value
     */
    function createSeq($seq_name, $start_value = 1)
    {
        $seq_table_name = $this->qid($seq_name);
        $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $seq_table_name));
        $this->execute(sprintf('INSERT INTO %s VALUES (%s)', $seq_table_name, $start_value));
    }
    
    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seq_name
     */
    function dropSeq($seq_name)
    {
    	return $this->execute(sprintf($this->DROP_SEQ_SQL, $this->qid($seq_name)));
    }
    
    /**
     * 获取自增字段的最后一个值或者 nextID() 方法产生的最后一个值
     *
     * 某些数据库（例如 MySQL）可以将一个字段设置为自增。
     * 也就是每次往数据表插入一条记录，该字段的都会自动填充一个更大的新值。
     *
     * insertID() 方法可以获得最后一次插入记录时产生的自增字段值，或者最后一次调用 nextID() 返回的值。
     *
     * 如果在一次操作中，插入了多条记录，那么 insertID() 有可能返回的是第一条记录的自增值。
     * 这个问题是由数据库本身的实现决定的。
     *
     * @return int
     */
    function insertID()
    {
        return $this->mssql_insert_id($this->_conn);
    }
    
    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * 这些操作通常是插入记录、更新记录以及删除记录。
     * 不同的数据库对于其他操作，也可能影响到 affectedRows() 返回的值。
     *
     * @return int
     */
    function affectedRows()
    {
        return mssql_rows_affected($this->_conn);
    }

    /**
     * 执行一个查询，返回一个查询对象或者 boolean 值，出错时抛出异常
     *
     * $sql 是要执行的 SQL 语句字符串，而 $inputarr 则是提供给 SQL 语句中参数占位符需要的值。
     *
     * 如果执行的查询是诸如 INSERT、DELETE、UPDATE 等不会返回结果集的操作，
     * 则 execute() 执行成功后会返回 true，失败时将抛出异常。
     *
     * 如果执行的查询是 SELECT 等会返回结果集的操作，
     * 则 execute() 执行成功后会返回一个 DBO_Result 对象，失败时将抛出异常。
     *
     * OK_Result_Abstract 对象封装了查询结果句柄，而不是结果集。
     * 因此要获得查询的数据，需要调用 OK_Result_Abstract 的 fetchAll() 等方法。
     *
     * 如果希望执行 SQL 后直接获得结果集，可以使用驱动的 getAll()、getRow() 等方法。
     *
     * example:
     * @code
     * $sql = "INSERT INTO posts (title, body) VALUES (?, ?)";
     * $dbo->execute($sql, array($title, $body));
     * @endcode
     *
     * example:
     * @code
     * $sql = "SELECT * FROM posts WHERE post_id < 12";
     * $handle = $dbo->execute($sql);
     * $rowset = $handle->fetchAll();
     * $handle->free();
     * @endcode
     *
     * @param string $sql
     * @param array $inputarr
     * @param bool $throw
     *
     * @return OK_Result_Abstract
     */
    function execute($sql, $inputarr = null,$throw = true)
    {
        //执行SQL前,将编码进行转换
        OK::loadClass('Helper_Util');        
        $sql = Helper_Util::autoCharset($sql,'utf-8','gbk');
        
        if (is_array($inputarr)) {
            $sql = $this->_prepareSql($sql, $inputarr);
        }
        if (! $this->isConnected())
        {
            $this->connect();
        }
        $result = @mssql_query($sql, $this->_conn);

        OK_Log::log($sql, OK_Log::DEBUG);
        
        if (is_resource($result))
        {
            return new OK_Result_Mssql($result, $this->_fetch_mode);
        } 
        elseif ($result) 
        {
            $this->_last_err = null;
            $this->_last_err_code = null;
            return $result;
        }else
        {
            $this->_last_err = $this->mssql_error($this->_conn);
            $this->_last_err_code = $this->mssql_errno($this->_conn);
            $this->_has_failed_query = true;

            if ($throw){
                throw new OK_Exception($sql, $this->_last_err, $this->_last_err_code);
            }
        }
        return false;
    }
    /*
	 * 进行限定范围的查询，并且返回 OK_Result_Abstract 对象，出错时抛出异常
	 *
	 * 使用 selectLimit()，可以限定 SELECT 查询返回的结果集的大小。
	 * $length 参数指定结果集最多包含多少条记录。而 $offset 参数则指定在查询结果中，从什么位置开始提取记录。
	 *
	 * 假设 SELECT * FROM posts ORDER BY post_id ASC 的查询结果一共有 500 条记录。
	 * 现在通过指定 $length 为 20，则可以限定只提取其中的 20 条记录作为结果集。
	 * 进一步指定 $offset 参数为 59，则可以从查询结果的第 60 条记录开始提取 20 条作为结果集。
	 *
	 * 注意：$offset 参数是从 0 开始计算的。因此 $offset 为 59 时，实际上是从第 60 条记录开始提取。
	 *
	 * selectLimit() 并不直接返回结果集，而是返回 OK_Result_Abstract 对象。
	 * 因此需要调用 OK_Result_Abstract 对象的 fetchAll() 等方法来获得数据。
	 *
	 * example:
	 * @code
	 * $sql = "SELECT * FROM posts WHERE created > ? ORDER BY post_id DESC";
	 * $length = 20;
	 * $offset = 0;
	 * $current = time() - 60 * 60 * 24 * 15; // 查询创建时间在 15 天内的记录
	 * $handle = $dbo->selectLimit($sql, $length, $offset, array($current));
	 * $rowset = $handle->fetchAll();
	 * $handle->free();
	 * @endcode
	 *
	 * @param string $sql
	 * @param int $length
	 * @param int $offset
	 * @param array $inputarr
	 *
	 * @return OK_Result_Abstract
	 */
    function selectLimit($sql, $offset = null, $length = 30, array $inputarr = null)
    {
        //执行SQL前,将编码进行转换
        OK::loadClass('Helper_Util');        
        $sql = Helper_Util::autoCharset($sql,'utf-8','gbk');
        
        if(is_numeric($length))
		{
			$intPageSize = intval($length);
			if($intPageSize < 0)
			{
			    $intPageSize = 0;
			}
		}
		else
		{
			$intPageSize = 0;
		}
		if(is_numeric($offset))
		{
			$intStartPosition = intval($offset);
			if($intStartPosition < 0)
			{
			    $intStartPosition = 0;
			}
		}else
		{
			$intStartPosition = 0;
		}
		$strSQL = $sql;
		$strPattern = '/^\s*(SELECT\s+(ALL|DISTINCT)?(\s+TOP\s+\d+)?(.+))(\s+FROM\s+.+)';
		if(strripos($strSQL, 'WHERE')){
			$strPattern .= '(\s+WHERE\s+.+)';
		}
		if(strripos($strSQL, 'GROUP BY')){
			$strPattern .= '(\s+GROUP BY\s+.+)';
		}
		if(strripos($strSQL, 'HAVING')){
			$strPattern .= '(\s+HAVING\s+.+)';
		}
		if(strripos($strSQL, 'ORDER BY')){
			$strPattern .= '(\s+ORDER BY\s+.+)';
		}
		$strPattern .= '$/i';
		$arrMatches = array();
		if(preg_match($strPattern, $strSQL, $arrMatches))
		{
			$j = count($arrMatches);
			for($i = 0; $i < $j; $i ++)
			{
				$arrMatches[$i] = trim($arrMatches[$i]);
			}
			
			if(empty($arrMatches[3]) && $j > 5)
			{
				$strLimitSql = 'SELECT ' . $arrMatches[2] . ' TOP ' . $intPageSize . ' ' . $arrMatches[4];
				$strLimitSql .= ' ' . $arrMatches[5];
				$strAlias = '';
				if(strpos($arrMatches[5], ','))
				{
					$strAlias = substr($arrMatches[5], 0, strpos($arrMatches[5], ','));
				}elseif(stristr($arrMatches[5], " JOIN "))
				{
					$strAlias = stristr($arrMatches[5], " JOIN ");
					$strAlias = substr($arrMatches[5], 0, strpos($arrMatches[5], $strAlias));
				}
				if(! empty($strAlias))
				{
					$strAlias = trim(substr($strAlias, 4));
					$arrAlias = split(' ', $strAlias);
					$strAlias = $arrAlias[0];
					if(strtoupper($arrAlias[1]) == 'AS')
					{
						$strAlias = $arrAlias[2];
					}
					elseif(! in_array(strtoupper($arrAlias[1]), array('INNER','LEFT','JOIN','RIGHT','FULL')))
					{
						$strAlias = $arrAlias[1];
					}
					//$strAlias = trim(substr($strAlias, strrpos($strAlias, ' ')));
					if(! empty($strAlias))
					{
					    $strAlias .= '.';
					}
				}
				if($j > 6)
				{
					if(strtoupper(substr($arrMatches[6], 0, 5)) == 'WHERE')
					{
						$strLimitSql .= ' WHERE (' . substr($arrMatches[6], 5) . ') AND ' . $strAlias . 'IDENTITYCOL NOT IN (';
						$strLimitSql .= 'SELECT ' . $arrMatches[2] . ' TOP ' . $intStartPosition . ' ' . $strAlias . 'IDENTITYCOL ' . $arrMatches[5];
						for($i = 6; $i < $j; $i ++)
						{
							$strLimitSql .= ' ' . $arrMatches[$i];
						}
						$strLimitSql .= ')';
						for($i = 7; $i < $j; $i ++)
						{
							$strLimitSql .= ' ' . $arrMatches[$i];
						}
					}
					else
					{
						$strLimitSql .= ' WHERE ' . $strAlias . 'IDENTITYCOL NOT IN (';
						$strLimitSql .= 'SELECT ' . $arrMatches[2] . ' TOP ' . $intStartPosition . ' ' . $strAlias . 'IDENTITYCOL ' . $arrMatches[5];
						for($i = 6; $i < $j; $i ++){
							$strLimitSql .= ' ' . $arrMatches[$i];
						}
						$strLimitSql .= ')';
						for($i = 6; $i < $j; $i ++){
							$strLimitSql .= ' ' . $arrMatches[$i];
						}
					}
				}
				else
				{
					$strLimitSql .= ' WHERE ' . $strAlias . 'IDENTITYCOL NOT IN (';
					$strLimitSql .= 'SELECT ' . $arrMatches[2] . ' TOP ' . $intStartPosition . ' ' . $strAlias . 'IDENTITYCOL ' . $arrMatches[5];
					$strLimitSql .= ')';
				}
				
				return  $this->execute($strLimitSql,$inputarr);
			}
			return false;
		}
		return false;
    }
    /**
     * 开始一个事务
     *
     * 调用 startTrans() 开始一个事务后，应该在关闭数据库连接前调用 completeTrans() 提交或回滚事务。
     */
    function startTrans()
    {
    	$this->_trans_count += 1;
        if ($this->_trans_count == 1) {
            $this->execute('BEGIN TRAN');
        }
    }
    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param $commit_on_no_errors
     */
    function completeTrans($commit_on_no_errors = true)
    {
    	if ($this->_trans_count < 1) { return false; }
        if ($this->_trans_count > 1) {
            $this->_trans_count -= 1;
            return true;
        }
        $this->_trans_count = 0;

        if ($this->_trans_count && $commit_on_no_errors) {
            $ret = $this->execute('COMMIT TRAN');
            return $ret;
        } else {
            $this->execute('ROLLBACK TRAN');
            return false;
        }
    }
    /**
     * 返回指定数据表（或者视图）的元数据
     *
     * 返回的结果是一个二维数组，每一项为一个字段的元数据。
     * 每个字段包含下列属性：
     *
     * - name:            字段名
     * - scale:           小数位数
     * - type:            字段类型
     * - ptype:           简单字段类型（与数据库无关）
     * - length:          最大长度
     * - not_null:        是否不允许保存 NULL 值
     * - pk:              是否是主键
     * - auto_incr:       是否是自动增量字段
     * - binary:          是否是二进制数据
     * - unsigned:        是否是无符号数值
     * - has_default:     是否有默认值
     * - default:         默认值
     * - desc:            字段描述
     *
     * ptype 是下列值之一：
     *
     * - c char/varchar 等类型
     * - x text 等类型
     * - b 二进制数据
     * - n 数值或者浮点数
     * - d 日期
     * - t TimeStamp
     * - l 逻辑布尔值
     * - i 整数
     * - r 自动增量
     * - p 非自增的主键字段
     *
     * @param string $table_name
     *
     * @return array
     */
    function metaColumns($table_name)
    {
        static $typeMap = array(
            'BIT'           => 'l',
            'TINYINT'       => 'l',
            'BOOL'          => 'l',
            'BOOLEAN'       => 'l',
            'SMALLINT'      => 'i',
            'MEDIUMINT'     => 'i',
            'INT'           => 'i',
            'INTEGER'       => 'i',
            'BIGINT'        => 'i',
            'FLOAT'         => 'n',
            'DOUBLE'        => 'n',
            'DOUBLEPRECISION' => 'n',
			'REAL'          => 'n',
			'NUMERIC'       => 'n',
            'DECIMAL'       => 'n',
            'DEC'           => 'n',
			'MONEY'         => 'n',
			'SMALLMONEY'    => 'n',


            'DATE'          => 'd',
            'DATETIME'      => 't',
			'SMALLDATETIME' => 't',
            'TIMESTAMP'     => 't',
            'TIME'          => 't',
            'YEAR'          => 'i',

            'CHAR'          => 'c',
            'NCHAR'         => 'c',
            'VARCHAR'       => 'c',
            'NVARCHAR'      => 'c',
            'BINARY'        => 'b',
            'VARBINARY'     => 'b',
            'TINYBLOB'      => 'x',
            'TINYTEXT'      => 'x',
            'BLOB'          => 'x',
            'TEXT'          => 'x',
			'NTEXT'         => 'x',
            'MEDIUMBLOB'    => 'x',
            'MEDIUMTEXT'    => 'x',
            'LONGBLOB'      => 'x',
            'LONGTEXT'      => 'x',
            'ENUM'          => 'c',
            'SET'           => 'c'
        );
        $arr=explode('.',$table_name);
        if (count($arr)==2)$table_name=$arr[1];
        //获得原始数据
        $rs = $this->execute(sprintf($this->META_COLUMNS_SQL, $table_name, $table_name));
    	if (!$rs) {return false;}
        $retarr = array();
        $rs->fetch_mode = OK_DB::FETCH_MODE_ASSOC;
        $rs->result_field_name_lower = true;
        
        while (($row = $rs->fetchRow())){
            $field = array();
            //name:字段名
            $field['name'] = $row['field'];
            $type = strtolower($row['datatype']);
            
            
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
            $field['ptype'] = @$typeMap[strtoupper($field['type'])];
            if ($field['ptype'] == 'c' && $field['length'] > 255) {
                $field['ptype'] = 'x';
            }
            
            $field['not_null'] = ($row['nullable'] != 'YES');
            
            $field['pk'] = ($row['primarykey'] == 'PRIMARY KEY');
            
            $field['auto_incr'] = (strpos($row['extra'], 'auto_increment') !== false);
            if ($field['auto_incr'])
            {
                $field['ptype'] = 'r';
            }
            $field['binary'] = (strpos($type, 'blob') !== false);
            $field['unsigned'] = (strpos($type, 'unsigned') !== false);
            if (! $field['binary'])
            {
                $d = $row['defaultvalue'];
                if ($d != '' && strtolower($d) != 'null')
                {
                    $field['has_default'] = true;
                    $field['default'] = $d;
                }
                else
                {
                    $field['has_default'] = false;
                    $field['default'] = null;
                }
            }
            
            if ($field['type'] == 'tinyint' && $field['length'] == 1)
            {
                $field['ptype'] = 'l';
            }
            
            $field['desc'] = ! empty($row['comment']) ? $row['comment'] : '';
            if (! is_null($field['default']))
            {
                switch ($field['ptype'])
                {
                case 'i':
                    $field['default'] = intval($field['default']);
                    break;
                case 'n':
                    $field['default'] = doubleval($field['default']);
                    break;
                case 'l':
                    $field['default'] = (bool) $field['default'];
                }
            }
            $retarr[strtolower($field['name'])] = $field;
        }
        return $retarr;
    }
    /**
     * 获得所有数据表的名称
     *
     * @param string $pattern
     * @param string $schema
     *
     * @return array
     */
    function metaTables($pattern = null, $schema = null)
    {
    	return $this->getCol('select name from sysobjects where xtype=\'U\'');
    }
    /**
     * 根据 SQL 语句和提供的参数数组，生成最终的 SQL 语句
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return string
     */
    protected function _prepareSql($sql, & $inputarr)
    {
        $sqlarr = explode('?', $sql);
        $sql = '';
        $ix = 0;
        foreach ($inputarr as $v) {
            $sql .= $sqlarr[$ix];
            $typ = gettype($v);
            if ($typ == 'string') {
                $sql .= $this->qstr($v);
            } else if ($typ == 'double') {
                $sql .= $this->qstr(str_replace(',', '.', $v));
            } else if ($typ == 'boolean') {
                $sql .= $v ? $this->TRUE_VALUE : $this->FALSE_VALUE;
            } else if ($v === null) {
                $sql .= 'NULL';
            } else {
                $sql .= $v;
            }
            $ix += 1;
        }
        if (isset($sqlarr[$ix])) {
            $sql .= $sqlarr[$ix];
        }
        return $sql;
    }
	protected function query_scalar_function($sql, &$conn = false)
	{
		if (empty($conn)) {
			$rs = @mssql_query($sql, $this->_conn);
		} else {
			$rs = @mssql_query($sql, $conn);
		}
		if (!$rs) return false;
		$arr = mssql_fetch_array($rs);
		mssql_free_result($rs);
		if (is_array($arr)) {
			return $arr[0];
		} else { 
			return -1;
		}
	}
    /**
     * 返回上一个 MsSQL 函数的错误号码
     *
     * @param resource link_identifier $conn
     *
     * @return int
     */
	protected function mssql_errno(&$conn = false)
	{
		$errorSQL = "SELECT @@ERROR";
		if (empty($conn)) {
			$num = $this->query_scalar_function($errorSQL);
		} else {
			$num = $this->query_scalar_function($errorSQL, $conn);
		}
		return $num;
	}

    /**
     * 返回上一个 MsSQL 函数的错误文本
     *
     * @param resource link_identifier $conn
     *
     * @return string
     */
	protected function mssql_error(&$conn = false)
	{
		//将编码进行转换
        OK::loadClass('Helper_Util');        
	    return Helper_Util::autoCharset(mssql_get_last_message(),'gbk','utf-8');
	}
	
    /**
     * 取得上一步 INSERT 操作产生的 ID 
     *
     * @param resource link_identifier $conn
     *
     * @return int
     */
	protected function mssql_insert_id(&$conn = false)
	{
		$identitySQL = 'SELECT @@IDENTITY'; // 'SELECT SCOPE_IDENTITY'; 'SELECT IDENT_CURRENT(table_name)' # for mssql 2000
		if (empty($conn)) {
			$id = $this->query_scalar_function($identitySQL);
		} else {
			$id = $this->query_scalar_function($identitySQL, $conn);
		}
		return $id;
	}
	protected function mssql_real_escape_string($value)
	{
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		$value = str_replace("'", "''", $value);
		return $value;
	}
	/**
	 * 格式化getFullName
	 */
	function FomatFullName($fullName)
	{
		$result=array();
		$arr=explode('.',$fullName);
		if ($arr[2]) {
			$result['field']=$arr[2];
			$result['table']=$arr[1];
			$result['database']=$arr[0];
			return $result;
		}
		if ($arr[1]) {
			$result['table']=$arr[1];
			$result['database']=$arr[0];
			return $result;
		}
		return $fullName;
	}
}