<?php
/**
 * 定义 OK_Cache_SerializeFileCache 类
 *
 * @package cache
 */
class OK_Cache_SerializeFileCache
{
	/**
	 * 默认的缓存策略
	 *
	 * -  life_time: 缓存有效时间（秒），默认值 900
	 *    如果设置为 0 表示缓存总是失效，设置为 -1 或其他比 0 小的值则表示不检查缓存有效期。
	 *
	 * -  cache_dir: 缓存目录（必须指定）
	 *
	 * @var array
	 */
	protected $_default_policy = array('life_time' => 900 , 'cache_dir' => null);
	/**
	 * 构造函数
	 *
	 * @param 默认的缓存策略 $default_policy
	 */
	function __construct (array $default_policy = null)
	{
		if (! is_null($default_policy)) {
			$this->_default_policy = array_merge($this->_default_policy, $default_policy);
		}
		if (empty($this->_default_policy['cache_dir'])) {
			$this->_default_policy['cache_dir'] = OK::ini('runtime_cache_dir');
		}
		$this->_default_policy['cache_dir'] = rtrim($this->_default_policy['cache_dir'], '/\\');
	}
	/**
	 * 写入缓存
	 *
	 * @param string $id
	 * @param mixed $data
	 * @param array $policy
	 */
	function set ($id, $data, array $policy = null)
	{
		$policy = $this->_policy($policy);
		$path = $this->_path($id, $policy);
		$content = array('cacheid' => $id, 'expired' => time() + $policy['life_time'] , 'data' => $data);
		$content = serialize($content);
		
		// 写入缓存
		file_put_contents($path, $content, LOCK_EX);
		clearstatcache();
	}
	/**
	 * 读取缓存，失败或缓存撒失效时返回 false
	 *
	 * @param string $id
	 * @param array $policy
	 *
	 * @return mixed
	 */
	function get ($id, array $policy = null)
	{
		$policy = $this->_policy($policy);
		$path = $this->_path($id, $policy);
		if (! file_exists($path)) {
			return false;
		}
		$data = @unserialize(file_get_contents($path));
		if (! is_array($data) || ! isset($data['expired'])) {
			return false;
		}
		
		if ($policy['life_time'] < 0) {
			return $data['data'];
		} else {
			return ($data['expired'] > time()) ? $data['data'] : false;
		}
	}
	/**
	 * 删除指定的缓存
	 *
	 * @param string $id
	 * @param array $policy
	 */
	function remove ($id, array $policy = null)
	{
		$path = $this->_path($id, $this->_policy($policy));
		if (is_file($path)) {
			unlink($path);
		}
	}
	/**
	 * 确定缓存文件名
	 *
	 * @param string $id
	 * @param array $policy
	 *
	 * @return string
	 */
	protected function _path ($id, array $policy)
	{
		return $policy['cache_dir'] . DS . 'cache_' . md5($id) . '_data.json';
	}
	/**
	 * 返回有效的策略选项
	 *
	 * @param array $policy
	 * @return array
	 */
	protected function _policy (array $policy = null)
	{
		return ! is_null($policy) ? array_merge($this->_default_policy, $policy) : $this->_default_policy;
	}
}

