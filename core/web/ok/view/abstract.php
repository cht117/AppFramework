<?php
/**
 * 视图控件基础类
 *
 */
class OK_View_Abstract
{
	/**
	 * 视图文件的扩展名
	 *
	 * @var string
	 */
	public $file_extname = 'php';
	/**
	 * 视图文件扩展名
	 * 
	 * @var string
	 */
	protected $_extname = 'php';
	/**
	 * 视图变量
	 *
	 * @var array
	 */
	protected $_vars;
	/**
	 * 视图文件所在目录
	 *
	 * @var string
	 */
	private $_view_dir;
	function __construct ($config)
	{
		$this->_view_dir = $config['template_dir'];
	}
	function setVars ($vars)
	{
		$this->_vars = $vars;
	}
	function setTplName ($name1, $name2 = null)
	{
		if (is_null($name2)) {
			$name = $name1;
		} else {
			$name = $name2 . DS . $name1;
		}
		$this->_name = $name . $this->_extname;
	}
	function execute ()
	{
		$tpl = $this->_view_dir . DS . $this->_name;
		if (is_file($tpl)) {
			extract($this->_vars);
			include $tpl;
		}
	}
	/**
	 * 载入一个视图片段
	 *
	 * @param string $element_name
	 * @param array $vars
	 *
	 * @access public
	 */
	protected function _element ($element_name, array $vars = null)
	{
		$filename = $this->_view_dir . DS . "_elements" . DS . $element_name . "_element.{$this->_extname}";
		$this->_include($filename, $vars);
	}
	/**
	 * 载入视图文件
	 */
	protected function _include ($___filename, array $___vars = null)
	{
		$this->_extname = pathinfo($___filename, PATHINFO_EXTENSION);
		//pecho($___filename);
		//extract($this->_vars);
		if (is_array($___vars))
			extract($___vars);
		include $___filename;
	}
}
?>