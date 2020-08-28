<?php
/**
 * 定义 OK_DB_Exception 类
 *
 * @package exception
 */

/**
 * OK_DB_Exception 用于封装数据库操作相关的异常
 *
 * @package exception
 */
class OK_DB_Exception extends OK_Exception
{
	/**
	 * 引发异常的 SQL 语句
	 *
	 * @var string
	 */
	public $sql;

	function __construct($sql, $error, $errcode = 0)
	{
		$this->sql = $sql;
		
		parent::__construct($error, $errcode);
	}
	/**
	 * 异常附加操作
	 * @see core/ok/OK_Exception::additional()
	 */
    function additional()
    {
    	// 将错误的SQL记录到日志文件中.
    	//Logger::getLogger('system.exception.sql')->error(str_replace("\n",' ',$this->sql));
    }
}

