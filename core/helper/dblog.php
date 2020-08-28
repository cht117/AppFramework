<?php
/**
 * 定义 Helper_DbLog 类
 *
 * @package helper
 */
/**
 * Helper_DbLog 类提供缓存某一次查询日志记录并打印
 *
 * @package helper
 */
abstract class Helper_DbLog
{
	/**
	 * 日志数据
	 * @var array
	 */
	static $data = array();
	/**
	 * 设置当前页面URL
	 */
	static function url()
	{
		static $url = null;
		if ($url == null)
		{
			$url = $_SERVER['HTTP_HOST'].  $_SERVER['REQUEST_URI'];
		}
		return $url;
	}
	/**
	 * 设置当前页面唯一标识
	 */
	static function key()
	{
		static $key = null;
		if ($key == null)
		{
			$key = md5(microtime(1));
			//注册关闭调用函数
			register_shutdown_function('Helper_DbLog::dump');
		}
		return $key;
	}
	
	/**
	 * 记录查询的SQL
	 * @param string $value
	 */
	static function sql($database,$sql)
	{
		self::dump();
		self::$data['sql'] = $sql;
		self::$data['database'] = $database;
		self::$data['url'] = self::url();
		self::$data['key'] = self::key();
	}
	
	/**
	 * 设置运行语句所需时间
	 * @param string $value
	 */
	static function time($value)
	{
		self::$data['time'] = $value;
	}
	/**
	 * 设置该结果集条目数
	 * @param string $value
	 */
	static function num($value)
	{
		self::$data['num'] = $value;
	}
	
	/**
	 * 缓冲打印到日志系统
	 */
	static function dump()
	{
		// 如果有SQL已经设置到SQL则开始打印
		if (!empty(self::$data['sql']))
		{
			$data = self::$data;
			$data = @eval("return ".iconv('GBK','UTF-8//IGNORE', var_export($data,1) ).';' );
			$log = json_encode($data);
			Logger::getLogger('info.db.detailedlog')->apps('info.db.detailedlog')->debug($log);
		}
		self::$data = array();
	}
	
	
	
}

