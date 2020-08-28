<?php
/**
 * 数据 meta 信息缓存更新助手
 * @author FIDOO
 *
 */
abstract class Helper_Meta_CacheUpdate
{
	static public function execute($database, $table_name)
	{
		$logs = array();
		
		//根据数据名称查询数据源
		$dsn = OK::ini('db_dsn_pool');
		foreach ($dsn as $key => $value)
		{
			if ($value['database'] == $database) {
				try {
					$conn = OK_DB::getConn($key);
					$logs[] = "OK:	" . self::setupMeta($conn, $table_name);
				}catch (Exception $ex){
					$logs[] = "Error:	With Message '" . $ex->getMessage() . "'";
				}
			}
		}
		return $logs;
	}
	/**
	 * 设置数据表的元数据缓存
	 * @param OK_Adapter_Abstract $conn
	 * @param string $table_name
	 */
	static private function setupMeta($conn, $table_name)
	{
		$cache_id = $conn->getID() . '-' . $table_name;
		
		// 尝试从缓存读取
		$policy = array
		(
			'encoding_filename' => true,
			'serialize' => true,
			'life_time' => OK::ini('db_meta_lifetime'),
			'cache_dir' => OK::ini('runtime_cache_dir'),
		);

		$backend = OK::ini('db_meta_cache_backend');
	
		// 从数据库获得 meta
		$meta = $conn->metaColumns($table_name);
		$fields = array();
		foreach ($meta as $key=>$field)
		{
			// 修改统一转换 meta 名为小写
			$fields[strtolower($field['name'])] = true;
			$meta[$key]['name'] = strtolower($field['name']);
		}
	
		$data = array($meta, $fields);
		// 缓存数据
		OK::writeCache($cache_id, $data, $policy, $backend);
		
		return self::path($cache_id, $policy);
	}
	
	/**
	 * 确定缓存文件名
	 *
	 * @param string $id
	 * @param array $policy
	 *
	 * @return string
	 */
	static private function path ($id, array $policy)
	{
		return $policy['cache_dir'] . DS . 'cache_' . md5($id) . '_data.php';
	}
}