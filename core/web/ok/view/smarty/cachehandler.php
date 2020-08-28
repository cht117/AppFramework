<?php

class OK_View_Smarty_CacheHandler
{
	
	static $config = array();
	
	static function memcache_handler($action, &$smarty_obj, &$cache_content, $tpl_file = null, $cache_id = null, $compile_id = null, $exp_time = null)
	{
		// 是否压缩存储 需要 gzuncompress 函数支持
		$use_gzip = false;
		//缓存到 Memcache 的key
		$cache_key = implode('/', array($tpl_file , $cache_id , $compile_id));
		
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
					//$smarty_obj->trigger_error("OK_View_Smarty_CacheHandler::memcache_handler: get failed.");
				}
				if ($use_gzip && function_exists("gzuncompress")) {
					$return = gzuncompress($return);
				}
				break;
			case 'write':
				if ($use_gzip && function_exists("gzcompress")) {
					$cache_content = gzcompress($cache_content);
				}
				$return = $memcache->set($cache_key, $cache_content, false, 1800);
				if (! $return){
					//$smarty_obj->trigger_error("OK_View_Smarty_CacheHandler::memcache_handler: set failed.");
				}
				break;
			case 'clear':
				if (empty($cache_key) && empty($compile_id) && empty($tpl_file)) {
					$return = $memcache->flush();
				} else {
					$return = $memcache->delete($cache_key);
				}
				if (! $return) {
					//$smarty_obj->trigger_error("OK_View_Smarty_CacheHandler::memcache_handler: clear failed.");
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