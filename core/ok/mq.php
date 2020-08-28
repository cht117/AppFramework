<?php
class OK_MQ
{
	/**
	 * 获取一个MQ对象
	 * @param string $dsn_name
	 * @throws OK_Exception
	 * @return OK_MQ_Abstract
	 */
	public static function getConn($dsn_name = 'default')
	{
		if (empty($dsn_name)) {
			$dsn_name = 'default';
		}
	
		$dsn = OK::ini('mq_dsn_pool/' . $dsn_name);
		if (empty($dsn)) {
			trigger_error('invalid mq dsn');
			throw new OK_Exception(__('Invalid MQ DSN.'));
		}
		$objid = "mq_" .  md5(serialize($dsn));
		if (OK::isRegistered($objid))
		{
			return OK::registry($objid);
		}
		$dbtype = $dsn['driver'];
		
		$class_name = 'OK_MQ_' . ucfirst($dbtype);
		
		$dbo = new $class_name($dsn, $objid);
		
		OK::register($dbo, $objid);
		
		return $dbo;
	}
}