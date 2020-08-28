<?php
/**
 * 简易mongodb连接类
 * @author FIDOO
 *
 */
abstract class OK_Mongo
{
	/**
	 * 获得一个mongo连接对象, 如果有主从时默认优先从从库读取数据
	 * @param string $dsn_name
	 * @param bool $force_new	是否新开连接
	 * @throws OK_Exception
	 * @return Ambigous <object, multitype:>|MongoDB
	 */
	public static function getConn($dsn_name = 'default', $force_new = false)
	{
		if (empty($dsn_name)) {
			$dsn_name = 'default';
		}
		
		$dsn = OK::ini('mongo_dsn_pool/' . $dsn_name);
		
		if (empty($dsn)) {
			trigger_error('invalid mongo dsn');
			throw new OK_Exception(__('Invalid Mongo DSN.'));
		}
		$objid = "mongodb_" .  md5(serialize($dsn));
		if ($force_new == false && OK::isRegistered($objid))
		{
			return OK::registry($objid);
		}
		
		$config = $auth = array();
		if (isset($dsn['username']) && isset($dsn['password']))
		{
			if ($dsn['authMode'] == 'db') {
				$auth['username'] = $dsn['username'];
				$auth['password'] = $dsn['password'];
			}else {
				$config['username'] = $dsn['username'];
				$config['password'] = $dsn['password'];
				$config['db'] = $dsn['db'];
			}

		}
		if ($dsn['replicaSet']) {
			$config['replicaSet'] = $dsn['replicaSet'];
		}
		//$config['db'] = $dsn['db'];
		
		$close = 0;
		while (true)
		{
			//重试3次
			if ($close < 2) {
				try {
					$dbo = self::getMongoDB($dsn['server'], $config, $dsn['db'], $auth);
				}catch (Exception $ex) {
					$close++;
					continue;
				}
			}else {
				$dbo = self::getMongoDB($dsn['server'], $config, $dsn['db'], $auth);
			}
			break;
		}
		
		OK::register($dbo, $objid);
		return $dbo;
	}
	
	/**
	 * 获取一个更新选项数组,安全写入并且不存在则添加数据
	 * @return array
	 */
	public static function getUpdateOption()
	{
		static $option = array('safe'=>true,'fsync'=>false,'upsert'=>true);
		return $option;
	}
	/**
	 * 设置优先从主库读取数据
	 * @param MongoDB $mongo
	 */
	public static function setPrimary(MongoDB $mongo)
	{
		$mongo->setReadPreference(MongoClient::RP_PRIMARY_PREFERRED);
	}
	/**
	 * 设置优先从从库读取数据
	 * @param MongoDB $mongo
	 */
	public static function setSecondary(MongoDB $mongo)
	{
		$mongo->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
	}
	/**
	 * 建立一个MongoDB对象,如果有主从时默认优先从从库读取数据
	 * @param string $server
	 * @param array $config
	 * @return MongoDB
	 */
	private static function getMongoDB($server, $config, $db, $auth)
	{
		$mongo = new MongoClient("mongodb://{$server}", $config);
		$mongo->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
		$dbo = $mongo->selectDB($db);
		if (!empty($auth)) {
			$dbo->authenticate($auth['username'], $auth['password']);
		}
		
		return $dbo;
	}
	/**
	 * 注销一个已注册的mongo连接对象
	 * @param string $dsn_name
	 * @throws OK_Exception
	 */
	public static function close($dsn_name = 'default')
	{
		$dsn = OK::ini('mongo_dsn_pool/' . $dsn_name);
	
		if (empty($dsn)) {
			trigger_error('invalid mongo dsn');
			throw new OK_Exception(__('Invalid Mongo DSN.'));
		}
		$objid = "mongodb_" .  md5(serialize($dsn));
		if (OK::isRegistered($objid))
		{
			OK::unregister($objid);
		}
	}
}