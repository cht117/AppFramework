<?php

class OK_View_Smarty_CompiledHandler
{
	
	static $config = array();
	
	static function memcache_handler($action, $params, &$smarty_obj)
	{
		// 是否压缩存储 需要 gzuncompress 函数支持
		$use_gzip = false;
		//缓存到 Memcache 的key
		$cache_key = $params['filename'];
		$cache_content = empty($params['contents']) ? '' : $params['contents'];
		
		if (empty(self::$config)) {
			return ;
		}
		
		$memcache = new Memcache();
		foreach (self::$config as $server)
		{
			$result = $memcache->addServer($server['host'], $server['port'], true, (int)$server['weight']);
			if (! $result) {
				throw new OK_Cache_Exception(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
			}
		}
		switch ($action)
		{
			case 'read':
				$return = $memcache->get($cache_key);
				if (! $return) {
					//$smarty_obj->trigger_error("memcache_cache_handler: get failed.");
				}
				if ($use_gzip && function_exists("gzuncompress")) {
					$return = gzuncompress($return);
				}
				break;
			case 'write':
				if ($use_gzip && function_exists("gzcompress")) {
					$cache_content = gzcompress($cache_content);
				}
				$return = $memcache->set($cache_key, $cache_content, false, 3600);
				if (! $return){
					//$smarty_obj->trigger_error("memcache_cache_handler: set failed.");
				}
				break;
			case 'clear':
				$return = $memcache->delete($cache_key);

				if (! $return) {
					//$smarty_obj->trigger_error("memcache_cache_handler: clear failed.");
				}
				break;
			default:
				$smarty_obj->trigger_error("cache_handler: unknown action \"$action\"");
				$return = false;
				break;
		}
		return $return;
	}

}