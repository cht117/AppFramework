<?php
class API_Worker_Abstract
{
	/**
	 * 运行时上下文
	 * @var API_Core_Context
	 */
	protected $context;
	
	function __construct()
	{
		/**
		 * 获取运行时上下文
		 * @var API_Core_Context
		 */
		$this->context = API_Core_Context::instance();
		
	}
	/**
	 * 获取应用级参数
	 * 
	 */
	public function getArgs()
	{
		$args = array();
		foreach ($this->context->apiConfig['API']['args'] as $value)
		{
			$tmp = $this->context->post($value);
			$args[] = ($tmp == null) ? $this->context->get($value) : $tmp ;
		}
		return $args;
	}
	
}