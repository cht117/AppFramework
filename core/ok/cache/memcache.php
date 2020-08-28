<?php
/**
 * 定义 OK_Cache_Memcache 类
 *
 * @package cache
 */

/**
 * OK_Cache_Memcache 使用 pecl-memcache 扩展来缓存数据
 *
 * @package cache
 */
class OK_Cache_Memcache
{
	/**
	 * 初始化配置
	*/
	static $object = array();
	
    static function getObject($dsn = 'lock')
    {
        if (empty(self::$object[$dsn])) {
            $config = OK::ini('memcache_pool/'.$dsn);
            $policy = array(
                'servers'   => $config,
            );
            self::$object[$dsn] = new OK_Cache_Memcache($policy);
        }
        return self::$object[$dsn];
    }
    /**
     * memcached连接句柄
     *
     * @var resource
     */
    protected $_conn;

    /**
     * 默认的缓存服务器
     *
     * @var array
     */
    protected $_default_server = array(
        /**
         * 缓存服务器地址或主机名
         */
        'host' => '192.168.8.218',

        /**
         * 缓存服务器端口
         */
        'port' => '11211',
    );

    /**
     * 默认的缓存策略
     *
     * @var array
     */
    protected $_default_policy = array(
        /**
         * 缓存服务器配置，参看$_default_server
         * 允许多个缓存服务器
         */
        'servers' => array(),

        /**
         * 是否压缩缓存数据
         */
        'compressed' => false,

        /**
         * 缓存有效时间
         *
         * 如果设置为 0 表示缓存永不过期
         */
        'life_time' => 900,

        /**
         * 是否使用持久连接
         */
        'persistent' => true,
    );

    /**
     * 构造函数
     *
     * @param 缓存策略 $policy
     */
    function __construct(array $policy = null)
    {
    	
        if (!extension_loaded('memcache'))
        {
            throw new OK_Cache_Exception('The memcache extension must be loaded before use!');
        }
        ini_set("memcache.hash_function","fnv");
		ini_set("memcache.hash_strategy","consistent");
        if(is_array($policy))
        {
            $this->_default_policy = array_merge($this->_default_policy, $policy);
        }
        
		
        if (empty($this->_default_policy['servers']))
        {
        	$memcachePool = OK::ini('memcache_pool/default');
            if (empty($memcachePool)) {
            	/**
            	 * 从配置文件获取 memcache 连接信息
            	 *
            	 */
            	$configFile = OK::ini('app_config/CONFIG_DIR').DS.'memcache.yaml';
            	$this->_default_policy['servers'] = Helper_YAML::loadCached($configFile);
            }else {
            	$this->_default_policy['servers'] = $memcachePool;
            }

        }
        $this->_conn = new Memcache();
        foreach ($this->_default_policy['servers'] as $server)
        {
            $result = $this->_conn->addServer($server['host'], $server['port'], $this->_default_policy['persistent'],(int)$server['weight']);
            if (!$result)
            {
                throw new OK_Cache_Exception(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
            }
        }

		//自动启用压缩策略，当数据大于2K时，以0.2的压缩比进行zlib
		$this->_conn->setCompressThreshold(2000, 0.2);
    }

    /**
     * 写入缓存
     *
     * @param string $id
     * @param mixed $data
     * @param array $policy
     * @return boolean
     */
    function set($id, $data, array $policy = null)
    {
        $compressed = isset($policy['compressed']) ? $policy['compressed'] : $this->_default_policy['compressed'];
        $life_time = isset($policy['life_time']) ? $policy['life_time'] : $this->_default_policy['life_time'];

        return $this->_conn->set($id, $data, $compressed ? MEMCACHE_COMPRESSED : 0, $life_time);
    }

    /**
     * 读取缓存，失败或缓存撒失效时返回 false
     *
     * @param string $id
     *
     * @return mixed
     */
    function get($id)
    {
        return $this->_conn->get($id);
    }

    /**
     * 删除指定的缓存
     *
     * @param string $id
     * @return boolean
     */
    function remove($id)
    {
        return $this->_conn->delete($id);
    }

    /**
     * 清除所有的缓存数据
     *
     * @return boolean
     */
    function clean()
    {
        return $this->_conn->flush();
    }
}

