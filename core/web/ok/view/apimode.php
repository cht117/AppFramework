<?php
/**
 * API 模式的视图控件
 * 将数据封装为 csv json php[serialize] xml 等格式
 *
 * {code: config}
 *  	'OK_View_ApiMode'		 => array(
 *  		'show_type'			=> 'json',
 *  		'charset'			=> array('input'=>'gbk','output'=>'utf-8'),
 *  	),
 * {/code}
 * 
 */
class OK_View_ApiMode
{
	/**
	 * 视图变量
	 *
	 * @var array
	 */
	protected $_vars;
	/**
	 * 显示类型 (json php[serialize] xml)
	 *
	 * @var string
	 */
	private $_show_type;
	/**
	 * 字符集转换,输入字符集
	 *
	 * @var string
	 */
	private $_charset_input;
	/**
	 * 字符集转换,输出字符集
	 *
	 * @var string
	 */
	private $_charset_output;
	
	function __construct ($config)
	{
		
		$this->_show_type = $config['show_type'];
		$this->_charset_input	= empty($config['charset']['input']) ? 'gbk' : $config['charset']['input'];
		$this->_charset_output	= empty($config['charset']['output']) ? 'utf-8' : $config['charset']['output'];
	}
	
	function setVars ($vars)
	{
		$this->_vars = $vars;
	}

	function execute ()
	{
		$vars = null;
		// 格式转换
		switch ($this->_show_type) 
		{
			case 'csv':
				break;
			case 'xml':
				@header("Content-Type: text/xml;charset=utf-8");
				$vars = '<?xml version="1.0" encoding="utf-8" ?>' .  "\n" . $vars;
				break;
			case 'php':
				@header("Content-Type: text/html;charset=utf-8");
				$vars = serialize($this->_vars);
				break;
			case 'json':
			//	@header( "Content-type: application/json" );
				@header("Content-Type: text/html;charset=utf-8");
				$vars = json_encode($this->formatArray($this->_vars));
				break;
			default:
				
		}
		//dprint(json_decode($vars));
		// 输出数据
		echo $vars;
		
	}
	/**
	 * 不需要模板文件, 直接生成数据输出
	 *
	 */
	function setTplName()
	{
		return ;
	}
	
	/**
	 * 格式化数据显示
	 *
	 * @param array $array
	 * @return array
	 */
	private function formatArray(array $array)
	{
		if ($this->_charset_input == $this->_charset_output) {
			return $array;
		}
		return @eval("return ".iconv($this->_charset_input,$this->_charset_output, var_export($array,1) ).';' );
		
	}
	
}
?>