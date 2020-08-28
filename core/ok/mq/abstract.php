<?php
/**
 * OK_MQ_Abstract 是所有消息队列服务的抽象基础类
 * @author FIDOO
 *
 */
abstract class OK_MQ_Abstract
{
	/**
	 * MQ连接信息
	 *
	 * @var mixed
	 */
	protected $_dsn;
	
	/**
	 * 数据库访问对象 ID
	 *
	 * @var string
	 */
	protected $_id;
	
	/**
	 * MQ服务Socket连接句柄
	 *
	 * @var resource
	 */
	protected $_conn;
	
	/**
	 * 发送消息服务
	 * 
	 * @param string $topic			队列名称，主题频道名称
	 * @param array|string $msg		发送的消息内容
	 * @param bool $isBackground	是否使用异步非阻塞方式
	 */
	abstract function send($topic, $msg, $isBackground = true);
	/**
	 * 构造函数
	 *
	 * @param mixed $dsn
	 * @param string $id
	 */
	protected function __construct($dsn, $id)
	{
		$this->_dsn = $dsn;
		$this->_id = $id;
	}
	/**
	 * 返回访问对象使用的 DSN
	 *
	 * @return mixed
	 */
	function getDSN()
	{
		return $this->_dsn;
	}
	
	/**
	 * 返回访问对象的 ID
	 *
	 * @return string
	 */
	function getID()
	{
		return $this->_id;
	}
	
	/**
	 * 关闭连接
	 */
	abstract function close();
	/**
	 * 关闭连接后清理资源
	 */
	protected function _clear()
	{
		$this->_conn = null;
	}
}