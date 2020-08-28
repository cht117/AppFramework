<?php
/**
 * 定义 OK_Cache_LockCache 类
 *
 * @package cache
 */

/**
 * OK_Cache_LockCache 使用 pecl-memcache 扩展来缓存数据
 *
 * @package cache
 */
class OK_Cache_LockCache
{
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
		/**
		 * 使用锁类型,默认是0 , 自动检查环境是否适合加锁
		 */
		'lockType' => 0,
		/**
		 * 如果使用文件锁则需要设置锁存储路径,无效的路径则不能启动锁
		 */
		'lockFileDir'	=> '',
	);
	
	private $lockType = 0;
	/**
	 * 对于本进程则永远是开锁状态
	 * @var array
	 */
	private $noLocks = array();

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
		
		//检查加锁环境,其他的锁方式从这里扩展
		if (!empty($this->_default_policy['lockType'])) {
			$this->lockType = $this->_default_policy['lockType'];
		}
		elseif (function_exists("shm_attach")) {
			$this->lockType = 2;
		}
		elseif (function_exists("eaccelerator_lock")) {
			$this->lockType = 1;
		}
		elseif (file_exists($this->_default_policy['lockFileDir'])) {
			$this->lockType = 3;
		}
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
		
		$data = array(
			'life_time'		=> $life_time + time(),
			'data_cache'	=> $data,
		);
		
		$return = $this->_conn->set($id, $data, $compressed ? MEMCACHE_COMPRESSED : 0, $life_time + 600);
		
		$this->unLock($id);
		
		return $return;
		
	}

	/**
	 * 读取缓存，失败或缓存撒失效时返回 false
	 *
	 * @param string $id
	 * @param array $policy		{callback, args} 使用回调函数 call_user_func_array 更新缓存
	 * @return mixed
	 */
	function get($id, $policy = null)
	{
		$data = $this->_conn->get($id);
		
		if (!isset($data['life_time'])) {
			if ($this->checkLock($id) == false) {
				//需要更新
				$this->setLock($id);
				if (!empty($policy)) {
					return $this->callbackSet($id, $policy);
				}
			}
			return false;
			
		}
		if ($data['life_time'] < time() && $this->checkLock($id) == false) {
			//需要更新
			$this->setLock($id);
			if (!empty($policy)) {
				return $this->callbackSet($id, $policy);
			}
			return false;
		}
		return $data['data_cache'];
		
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
	/**
	 * 使用回调函数更新缓存
	 * @param string $id
	 * @param array $policy		{callback, args}
	 * @return boolean
	 */
	protected function callbackSet($id, $policy)
	{
		if (empty($policy['callback'])) {
			return false;
		}
		if (empty($policy['args'])) {
			$policy['args'] = array();
		}
		$data = call_user_func_array($policy['callback'], $policy['args']);
		unset($policy['callback'], $policy['args']);
		$this->set($id, $data, $policy);
		return $data;
	}
	
	/**
	 * 检查某个key的锁是否存在
	 * 
	 * @param string $id		key
	 * @param number $level		检查级别,如果大于1则忽略加锁进程
	 * @return boolean
	 */
	function checkLock($id, $level = 0)
	{
		if ($this->noLocks[$id] && $level < 1) {
			return false;
		}
		switch ($this->lockType)
		{
			//sysvshm
			case 2:
				$lock = @shm_get_var($this->get_shm_id(), crc32($id));
				//pprint('lock',$lock,date('Y-m-d H:i:s',$lock), ($lock < time()) ? false : true);
				$lock = ($lock < time()) ? false : true;
				break;
			//eAccelerator
			case 1:
				//TODO: 此写法未测试考证
				$lock = eaccelerator_lock("lock_{$id}") ? false : true;
				break;
			case 3:
				$lock = false;
				$fp = $this->get_file_lock();
				if (!empty($fp)) {
					if (flock($fp, LOCK_EX))
					{
						$this->noLocks[$id] = true;
						$lock = false;
					}else {
						$lock = true;
					}
				}
				
				break;
			default:
				$lock = false;
		}
		
		
		return $lock;
	}
	/**
	 * 设置锁
	 * @param string $id
	 */
	function setLock($id)
	{
		$this->noLocks[$id] = true;
		
		switch ($this->lockType)
		{
			//sysvshm
			case 2:
				shm_put_var($this->get_shm_id(), crc32($id), time() + 60);
				break;
			//eAccelerator
			case 1:
				eaccelerator_lock("lock_{$id}");
				break;
			case 3:
				$fp = $this->get_file_lock();
				if (!empty($fp)) {
					flock($fp, LOCK_EX);
				}
				break;
			default:
				
		}
		
	}
	/**
	 * 解除锁
	 * @param string $id
	 */
	function unLock($id)
	{
		switch ($this->lockType)
		{
			//sysvshm
			case 2:
				@shm_remove_var($this->get_shm_id(),crc32($id));
				break;
			//eAccelerator
			case 1:
				eaccelerator_unlock("lock_{$id}");
				break;
			case 3:
				$fp = $this->get_file_lock();
				if (!empty($fp)) {
					flock($fp, LOCK_UN);
					clearstatcache();
				}
			default:
				
		}
		
	}
	/**
	 * 当使用 sysvshm 扩展时使用到
	 * @var unknown
	 */
	private $shm_id = null;
	
	/**
	 * 当使用 sysvshm 扩展时使用到,创建或关联一个现有的的共享内存ID
	 * @param string $sep_key	共享内存中存储序列号ID的KEY
	 */
	private function get_shm_id()
	{
		if (empty($this->shm_id)) {
			//存储在共享内存中地址
			$this->shm_id = shm_attach(ftok(__FILE__, 'a'));
		}
		return $this->shm_id;
	}
	/**
	 * 增加一个文件锁存储属性
	 * @var array
	 */
	private $fileLocks = array();
	/**
	 * 获取文件锁句柄
	 * @param string $id
	 * @return multitype:
	 */
	private function get_file_lock($id)
	{
		if (!isset($this->fileLocks[$id]))
		{
			$this->fileLocks[$id] = fopen($this->lockFileDir . '/' . crc32($id), 'w+');
		}

		return $this->fileLocks[$id];
	}
}

