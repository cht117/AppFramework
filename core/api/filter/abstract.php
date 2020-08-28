<?php
/**
 * filter 基础类
 * 
 * @author yuanwz@okooo.net
 *
 */
class API_Filter_Abstract
{
	/**
	 * 运行时上下文
	 * @var API_Core_Context
	 */
	protected $context;
	/**
	 * filter config
	 * @var array
	 */
	protected $config;
	
	function __construct($config)
	{
		/**
		 * 获取运行时上下文
		 * @var API_Core_Context
		 */
		$this->context = API_Core_Context::instance();
		
		$this->config = is_array($config) ? $config : array();

	}
	
	function init()
	{
		
	}
	
	/**
	 * 指示指定的动作
	 */
	function execute()
	{
		
	}
	
	function getConfig($key)
	{
		return empty($this->config[$key]) ? null : $this->config[$key];
	}
	
}