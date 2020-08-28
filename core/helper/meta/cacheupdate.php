<?php
/**
 * ���� meta ��Ϣ�����������
 * @author FIDOO
 *
 */
abstract class Helper_Meta_CacheUpdate
{
	static public function execute($database, $table_name)
	{
		$logs = array();
		
		//�����������Ʋ�ѯ����Դ
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
	 * �������ݱ��Ԫ���ݻ���
	 * @param OK_Adapter_Abstract $conn
	 * @param string $table_name
	 */
	static private function setupMeta($conn, $table_name)
	{
		$cache_id = $conn->getID() . '-' . $table_name;
		
		// ���Դӻ����ȡ
		$policy = array
		(
			'encoding_filename' => true,
			'serialize' => true,
			'life_time' => OK::ini('db_meta_lifetime'),
			'cache_dir' => OK::ini('runtime_cache_dir'),
		);

		$backend = OK::ini('db_meta_cache_backend');
	
		// �����ݿ��� meta
		$meta = $conn->metaColumns($table_name);
		$fields = array();
		foreach ($meta as $key=>$field)
		{
			// �޸�ͳһת�� meta ��ΪСд
			$fields[strtolower($field['name'])] = true;
			$meta[$key]['name'] = strtolower($field['name']);
		}
	
		$data = array($meta, $fields);
		// ��������
		OK::writeCache($cache_id, $data, $policy, $backend);
		
		return self::path($cache_id, $policy);
	}
	
	/**
	 * ȷ�������ļ���
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