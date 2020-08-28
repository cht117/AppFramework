<?php
/**
 * 定义 OK_Table 类
 *
 * @package database
 */

/**
 * OK_Table 类（表数据入口）封装数据表的 CRUD 操作
 *
 * @package database
 */
class OK_Table
{

    /**
     * 数据表的 schema
     *
     * @var string
     */
    public $schema;

    /**
     * 数据表名称
     *
     * @var string
     */
    public $name;

    /**
     * 数据表前缀
     *
     * @var string
     */
    public $prefix;

    /**
     * 主键字段名，如果是多个字段，则以逗号分割，或使用数组
     *
     * @var string|array
     */
    protected $_pk;

    /**
     * 指示是否使用了复合主键
     *
     * @var boolean
     */
    protected $_is_cpk;

    /**
     * 指示主键字段的总数
     *
     * @var int
     */
    protected $_pk_count;

    /**
     * 数据访问对象
     *
     * @var OK_Adapter_Abstract
     */
    protected $_conn;
    protected $_mode;
    /**
     * 数据库访问对象(只写)仅限读写分离模式
     *
     * @var OK_Adapter_Abstract
     */
    protected $_conn_w;
    /**
     * 数据库访问对象(只读)仅限读写分离模式
     *
     * @var OK_Adapter_Abstract
     */
    protected $_conn_r;

    /**
     * 当前表数据入口对象元信息的缓存id
     *
     * @var string
     */
    protected $_cache_id;

    /**
     * 指示表数据入口是否已经初始化
     *
     * @var boolean
     */
    private $_inited;

    /**
     * 数据表的元数据
     *
     * 元数据是一个二维数组，每个元素的键名就是全小写的字段名，而键值则是该字段的数据表定义。
     *
     * @var array
     */
    protected static $_meta = array();

    /**
     * 数据表的字段名
     *
     * @var array
     */
    protected static $_fields = array();

    /**
     * 构造 Table 实例
     *
     * $config 参数允许有下列选项：
     *   - schema:          指定数据表的 schema
     *   - name:      		指定数据表的名称
     *   - name_prefix: 	指定数据表的完整名称
     *   - pk:              指定主键字段名
     *   - conn:			指定数据库访问对象
     *   - dsn:				指定访问的数据库dsn,字符串,可以指定多个,使用','隔开 如果指定了conn则不生效
     *
     * @param array $config
     *
     * @return Table
     */
    function __construct(array $config = null)
    {
        if (! empty($config['schema']))
        {
            $this->schema = $config['schema'];
        }
        if (! empty($config['name']))
        {
            $this->name = $config['name'];
        }
        if (! empty($config['name_prefix']))
        {
            $this->prefix = $config['name_prefix'];
        }
        if (! empty($config['pk']))
        {
            $this->_pk = $config['pk'];
        }
        if (! empty($config['conn']) && is_object($config['conn']))
        {
            $this->setConn($config['conn']);
        }elseif (! empty($config['dsn']))
        {
        	$_dsns = explode(',',str_replace(chr(32),'',$config['dsn']));

        	$this->_mode = !empty($config['mode']) ? $config['mode'] : OK_DB::DB_RW_SEPARATE;
        	
        	switch ($this->_mode) {
        		case OK_DB::DB_RW_SEPARATE:
        			if (count($_dsns) > 1) {
	        			$this->setConnRW(OK_DB::getConn($_dsns[0]) , false);
	        			$this->setConnRW(OK_DB::getConn($_dsns[rand(1,count($_dsns)-2)]) , false);
        			}elseif (count($_dsns) == 1) {
        				$this->_mode = null;
        				$this->setConn(OK_DB::getConn($_dsns[0]));
        			}
        			break;
        		default:
        			$this->setConn(OK_DB::getConn($_dsns[rand(0,count($_dsns)-1)]));
        	}
        }
        
    }

    /**
     * 发起一个查询，获得一个 OK_Select 查询对象
     *
     * @return OK_Select
     */
    function select()
    {
        if (! $this->_inited)
        {
            $this->init();
        }
    	// 如何设置了读写模式则强制使用读模式(操作从数据库)
		if (!empty($this->_conn_r)) $this->_conn = $this->_conn_r;
		
        $select = new OK_Select($this->_conn);
        $select->from($this);
        $args = func_get_args();
        if (! empty($args))
        {
            call_user_func_array(array($select, 'where'), $args);
        }
        return $select;
    }

    /**
     * 创建一条记录
     *
     * @param array $row
     *   要插入的记录
     * @param boolean $return_pk_values
     *   是否返回新建记录的主键值
     *
     * @return array|null
     */
    function insert(array $row, $return_pk_values = false)
    {
    	// 如何设置了读写模式则强制使用写模式(操作主数据库)
		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
		
        if (! $this->_inited)
        {
            $this->init();
        }

        $insert_id = array();

        if ($return_pk_values)
        {
            $use_auto_incr = false;

            if ($this->_is_cpk)
            {
                // 假定复合主键必须提供所有主键的值
                foreach ($this->_pk as $pk)
                {
                    $insert_id[$pk] = $row[$pk];
                }
            }
            else
            {
                // 如果只有一个主键字段，并且主键字段不是自增，则通过 nextID() 获得一个主键值
                $pk = $this->_pk[0];
                if (empty($row[$pk]))
                {
                    unset($row[$pk]);
                    if (! self::$_meta[$this->_cache_id][$pk]['auto_incr'])
                    {
                        $row[$pk] = $this->nextID($pk);
                        $insert_id[$pk] = $row[$pk];
                    }
                    else
                    {
                        $use_auto_incr = true;
                    }
                }
                else
                {
                    $insert_id[$pk] = $row[$pk];
                }
            }
        }
        else
        {
            $pk = $this->_pk[0];
            if (! $this->_is_cpk && ! self::$_meta[$this->_cache_id][$pk]['auto_incr'] && empty($row[$pk]))
            {
                // 如果只有一个主键字段，并且主键字段不是自增，则通过 nextID() 获得一个主键值
                $pk = $this->_pk[0];
                $row[$pk] = $this->nextID($pk);
            }
        }

        $this->_conn->insert($this->getFullTableName(), $row, self::$_fields[$this->_cache_id]);

        if ($return_pk_values)
        {
            // 创建主表的记录成功后，尝试获取新记录的主键值
            if ($use_auto_incr)
            {
                $insert_id[$pk] = $this->_conn->insertID();
            }
            return $insert_id;
        }
        else
        {
            return null;
        }
    }

    /**
     * 更新记录
     *
     * 如果 $row 参数中包含所有主键字段的值，并且没有指定 $where 参数，则假定更新主键字段值相同的记录。
     *
     * 如果 $row 是一个 OK_Expr 表达式，则根据表达式内容更新数据库。
     *
     * @param array|OK_Expr $row 要更新的记录值
     * @param mixed $where 更新条件
     */
    function update($row, $where = null)
    {
    	// 如何设置了读写模式则强制使用写模式(操作主数据库)
		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
        
        if (!$this->_inited)
        {
            $this->init();
        }

        if (is_null($where))
        {
            if (is_array($row))
            {
                $where = array();
                foreach ($this->_pk as $pk)
                {
                    if (!isset($row[$pk]) || strlen($row[$pk] == 0))
                    {
                        $where = array();
                        break;
                    }
                    $where[$pk] = $row[$pk];
                }
                $where = array($where);
            }
            else
            {
                $where = null;
            }
        }
        elseif ($where)
        {
            $where = func_get_args();
            array_shift($where);
        }
        $this->_conn->update($this->getFullTableName(), $row, $where, self::$_fields[$this->_cache_id]);
    }

    /**
     * 删除符合条件的记录
     *
     * @param mixed $where
     */
    function delete($where)
    {
    	// 如何设置了读写模式则强制使用写模式(操作主数据库)
		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
		
        if (! $this->_inited)
        {
            $this->init();
        }

        if (is_int($where) || ((int) $where == $where && $where > 0))
        {
            // 如果 $where 是一个整数，则假定为主键字段值
            if ($this->_is_cpk)
            {
                // LC_MSG: 使用复合主键时，不允许通过直接指定主键值来删除记录.
                throw new OK_Table_Exception(__('使用复合主键时，不允许通过直接指定主键值来删除记录.'));
            }
            else
            {
                $where = array( array( $this->_pk[0] => (int) $where ) );
            }
        }
        else
        {
            $where = func_get_args();
        }
        $this->_conn->delete($this->getFullTableName(), $where);
    }
    /**
     * 执行一条完整的SQL语句
     */
    function dbTool($sql , $args = array())
    {
    	$is_select = $args[0];
    	
    	if ($is_select) {
	    	if (!empty($this->_conn_r)) $this->_conn = $this->_conn_r;
	    	return $this->_conn->execute($sql)->fetchAll();
    	}else {
    		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
    		return $this->_conn->execute($sql);
    	}
    		
    }
    /**
     * 对数据库开始一个事务
     * 调用 startTrans() 开始一个事务后，应该在关闭数据库连接前调用 completeTrans() 提交或回滚事务。
     *
     */
    function startTrans()
    {
    	// 如何设置了读写模式则强制使用写模式(操作主数据库)
		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
    	$this->_conn->startTrans();
    	
    }
    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param boolean $commit_on_no_errors
     */
    function completeTrans($commit_on_no_errors = true)
    {
    	// 如何设置了读写模式则强制使用写模式(操作主数据库)
		if (!empty($this->_conn_w)) $this->_conn = $this->_conn_w;
		$this->_conn->completeTrans($commit_on_no_errors);
		
    }

    /**
     * 返回数据表的完整名称（含 schema 和前缀）
     *
     * @return string
     */
    function getFullTableName()
    {
        if (!$this->_inited)
        {
            $this->_setupConn();
        }
        return (! empty($this->schema) ? "`{$this->schema}`." : '')
            . "`{$this->prefix}{$this->name}`";
    }

    /**
     * 为当前数据表的指定字段产生一个序列值
     *
     * @param string $field_name
     *
     * @return mixed
     */
    function nextID($field_name = null)
    {
        if (! $this->_inited)
        {
            $this->init();
        }
        if (is_null($field_name))
        {
            $field_name = $this->_pk[0];
        }
        return $this->_conn->nextID($this->getFullTableName(), $field_name);
    }

    /**
     * 返回所有字段的元数据
     *
     * @return array
     */
    function columns()
    {
        if (! $this->_inited)
        {
            $this->init();
        }
        return self::$_meta[$this->_cache_id];
    }

    /**
     * 返回主键字段名
     *
     * @return array
     */
    function getPK()
    {
        if (! $this->_inited)
        {
            $this->init();
        }
        return $this->_pk;
    }

    /**
     * 设置数据表的主键
     *
     * @param array|string $pk
     */
    function setPK($pk)
    {
        $this->_pk = OK::normalize($pk);
        $this->_pk_count = count($this->_pk);
        $this->_is_cpk = $this->_pk_count > 1;
    }

    /**
     * 确认是否是复合主键
     *
     * @return boolean
     */
    function isCompositePK()
    {
        if (! $this->_inited)
        {
            $this->init();
        }
        return $this->_is_cpk;
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return OK_Adapter_Abstract
     */
    function getConn()
    {
        if (! $this->_inited)
        {
            $this->init();
        }
        
        /**
         * 检查表数据入口采用什么数据库访问模式:
         * - 读写分离模式
         * - 平衡分流模式
         * 
         */
        if ($this->_mode == OK_DB::DB_RW_SEPARATE) {
        	$args = func_get_args();
        	if (empty($args[0])) {
        		if (! $this->_conn_r->isConnected()) $this->_conn_r->connect();
        		return $this->_conn_r;
        	}else {
        		if (! $this->_conn_w->isConnected()) $this->_conn_w->connect();
        		return $this->_conn_w;
        	}
        }
        return $this->_conn;
    }

    /**
     * 设置数据库访问对象
     *
     * @param OK_Adapter_Abstract $conn
     */
    function setConn(OK_Adapter_Abstract $conn)
    {
        $this->_conn = $conn;
        if (! $this->_conn->isConnected())
        {
            $this->_conn->connect();
        }
        if (empty($this->schema))
        {
        	//不自动拼凑库表名
            //$this->schema = $conn->getSchema();
        }
        if (empty($this->prefix))
        {
            $this->prefix = $conn->getTablePrefix();
        }
    }
    /**
     * 设置数据库访问对象(读写分离模式)
     *
     * @param OK_Adapter_Abstract $conn
     */
    function setConnRW(OK_Adapter_Abstract $conn ,$setConnect = TRUE )
    {
    	
    	
    	if (empty($this->_conn_w)) {
    		$this->_conn = $this->_conn_w = $conn;
    	}else {
    		$this->_conn = $this->_conn_r = $conn;
    	}
        if (! $this->_conn->isConnected() && $setConnect)
        {
            $this->_conn->connect();
        }
        /*if (empty($this->schema))
        {
            $this->schema = $conn->getSchema();
        }*/
        if (empty($this->prefix))
        {
            $this->prefix = $conn->getTablePrefix();
        }
    }
    
    /**
     * 关闭数据库操作的读写分离模式
     *
     */
    function close_db_rw()
    {
    	if ($this->_mode == OK_DB::DB_RW_SEPARATE) {
    		$this->_mode = OK_DB::DB_CLOSE_RW;
	    	if (!empty($this->_conn_w)) {
	    		$this->_conn = $this->_conn_w;
	    		$this->_conn_w = null;
	    		$this->_conn_r = null;
	    	}
    	}
    }
    
    /**
     * 初始化表数据入口
     */
    function init()
    {
        if ($this->_inited) { return; }
        $this->_inited = true;
        $this->_setupConn();
        $this->_setupTableName();
        $this->_setupMeta();
        $this->_setupPk();
    }

    /**
     * 设置表数据入口使用的数据库访问对象
     *
     * 继承类可以覆盖此方法来自行控制如何设置数据库访问对象。
     */
    protected function _setupConn()
    {
        if (! is_null($this->_conn))
        {
            return;
        }
        $this->setConn(OK_DB::getConn());
    }

    /**
     * 设置数据表名称
     *
     * 继承类可覆盖此方法来自行控制如何设置数据表名称。
     */
    protected function _setupTableName()
    {
        if (empty($this->name))
        {
            $arr = explode('_', get_class($this));
            $this->name = strtolower($arr[count($arr) - 1]);
        }
        elseif (strpos($this->name, '.'))
        {
            list ($this->schema, $this->name) = explode('.', $this->name);
        }
    }

    /**
     * 设置当前数据表的元数据
     */
    protected function _setupMeta()
    {
        $table_name = $this->getFullTableName();
        $this->_cache_id = $this->_conn->getID() . '-' . $table_name;
        if (isset(self::$_meta[$this->_cache_id]))
        {
            return;
        }

        $cached = OK::ini('db_meta_cached');

        if ($cached)
        {
            // 尝试从缓存读取
            $policy = array
            (
                'encoding_filename' => true,
                'serialize' => true,
                'life_time' => OK::ini('db_meta_lifetime'),
                'cache_dir' => OK::ini('runtime_cache_dir'),
            );

            $backend = OK::ini('db_meta_cache_backend');
            $data = OK::cache($this->_cache_id, $policy, $backend);
            if (is_array($data) && ! empty($data))
            {
                self::$_meta[$this->_cache_id] = $data[0];
                self::$_fields[$this->_cache_id] = $data[1];
                return;
            }
        }

        // 从数据库获得 meta
        $meta = $this->_conn->metaColumns($table_name);
        $fields = array();
        foreach ($meta as $key=>$field)
        {
        	// 修改统一转换 meta 名为小写
            $fields[strtolower($field['name'])] = true;
            $meta[$key]['name'] = strtolower($field['name']);
        }
        self::$_meta[$this->_cache_id] = $meta;
        self::$_fields[$this->_cache_id] = $fields;

        $data = array($meta, $fields);
        if ($cached)
        {
            // 缓存数据
            OK::writeCache($this->_cache_id, $data, $policy, $backend);
        }
    }

    /**
     * 设置数据表的主键
     *
     * 继承类可覆盖此方法来自行控制如何设置数据表主键。
     */
    protected function _setupPk()
    {
        if (empty($this->_pk))
        {
            // 尝试从 meta 中自动取得主键信息
            $this->_pk = array();
            foreach (self::$_meta[$this->_cache_id] as $field)
            {
                if ($field['pk'])
                {
                    $this->_pk[] = $field['name'];
                }
            }
        }

        $this->setPK($this->_pk);
    }
}

