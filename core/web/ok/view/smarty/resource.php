<?php
/**
 * Smarty 模板引擎 扩展资源类
 *
 */
class OK_View_Smarty_Resource
{
	private $resource_type;
	
	private $tpl_path;
	
	private $tpl_name;
	
	function __construct($resource_type = null)
	{
		$this->resource_type = $resource_type;
	}
	
    /**
     * 
     */
	function get_template($tpl_name, &$tpl_source, &$smarty_obj)
	{
		if (!$this->analyticalTpl($tpl_name)) {
			return false;
		}
		//$tpl_name = $this->tpl_name;
		$tpl_source = file_get_contents($this->tpl_path);
		$tpl_name = $this->tpl_name;
		return true;

	}
    
	function get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
	{
		if (!$this->analyticalTpl($tpl_name)) {
			return false;
		}
		//$tpl_name = $this->tpl_name;
		$tpl_timestamp = filemtime($this->tpl_path);
		return true;

	}
	
	function get_secure()
	{
		return true;
	}
	
	function get_trusted()
	{
		
	}
	
	protected function analyticalTpl($tpl_name)
	{
		/**
		 * 分析参数
		 * 
		 * 
		 */
		$tmp = explode('@',$tpl_name);
		
		if (count($tmp) == 2) {
			if (($moduleName = trim($tmp[0])) == false) {
				return false;
			}
			$fileName = $tmp[1];
		}else {
			return false;
		}
		if ($this->resource_type == 'module') {
			$tplPath = OK::ini('app_config/MODULE_DIR').DS.$moduleName.DS.'view'.DS.$fileName;
		}else {
			return false;
		}
		if (!is_file($tplPath)) {
			return false;
		}
		$this->tpl_name = $fileName;
		$this->tpl_path = $tplPath;
		return true;
	}
	
}
?>