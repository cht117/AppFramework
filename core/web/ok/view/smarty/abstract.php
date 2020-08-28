<?php
/**
 * 定义 OK_View_Smarty_Abstract 类 - Smarty模板引擎类
 * 
 * ! 参考文档
 * http://smarty-php.googlecode.com/svn/branches/Smarty3Dev/distribution/README
 *
 */
class OK_View_Smarty_Abstract
{

	/**
	 * 视图变量
	 *
	 * @var array
	 */
	protected $_vars = array();
	/**
	 * 视图名称
	 *
	 * @var unknown_type
	 */
	protected $_name;
	/**
	 * Smarty 类
	 *
	 * @var Smarty
	 */
	protected $_smarty;
	/**
	 * 缓存插件函数
	 * @param array|string $param
	 * @param string $content
	 * @param object $smarty
	 */
    static function smarty_block_cacheless($param, $content, &$smarty) 
    {
            return $content;
    }
	/**
	 * 构造函数
	 *
	 */
	function __construct($config)
	{
		if (is_file($config['view_class_dir'])) {
			
			if (empty($config['version']) || $config['version'] < 3.18) {
				$className = 'Smarty';
			}else {
				$className = 'SmartyBC';
			}
			if (!class_exists($className, false)) {
				include $config['view_class_dir'];
			}
			/* @var $smarty Smarty */
			$smarty = new $className();

			//兼容3.0
			if(method_exists($smarty, 'register_block')) {
				$smarty->register_block('cacheless', 'OK_View_Smarty_Abstract::smarty_block_cacheless', false);
			}
			$smarty->template_dir = $config['template_dir'];
			
			$smarty->left_delimiter = $config['left_delimiter'];
			$smarty->right_delimiter = $config['right_delimiter'];

			
			
			$smarty->compile_dir = $config['compile_dir'];
			/*
			if (!empty($config['compiled_handler_func'])) {
				$smarty->compiled_handler_func = $config['compiled_handler_func'];
				OK_View_Smarty_CompiledHandler::$config = $config['compiled_handler_conf'];
			}
			*/
			
			$smarty->compile_dir = $config['compile_dir'];
			if (!empty($config['cache_handler_func'])) {
				$smarty->cache_handler_func = $config['cache_handler_func'];
				OK_View_Smarty_CacheHandler::$config = $config['cache_handler_conf'];
			}else {
				//兼容旧的配置
				if (isset($config['cache_dir'])) {
					$smarty->cache_dir = $config['cache_dir'];
				}else {
					$smarty->cache_dir =  $config['compile_dir'];
				}
			}
			
			if (method_exists($smarty, 'allow_php_tag')) {
				$smarty->allow_php_tag = true;
			}elseif (method_exists($smarty, 'php_handling')) {
				$smarty->php_handling = true;
			}
			$smarty->allow_php_templates = true;
			
			/**
			 * 初始化模板资源
			 */
			$this->_smarty = $smarty;
		}else {
			die('File not found , '.$config['view_class_dir']);
		}
		
	}
	/**
	 * 设置模板变量
	 * @param array $vars
	 */
	function setVars($vars = array())
	{
		$this->_vars = $vars;
	}
	/**
	 * 设置模板名称
	 * @param string $name1
	 * @param string $name2
	 */
	function setTplName($name1 , $name2 = null)
	{
		if (is_null($name2)) {
			$name = $name1;
		}else {
			$name = $name2.'/'.$name1;
		}
		$this->_name = $name.'.tpl';
	}
	/**
	 * 渲染视图
	 */
	function execute()
	{
		foreach ((array) $this->_vars as $key => $value) {
			$this->_smarty->assign($key , $value);
		}
		if(empty($this->_cacheid)) {
			$this->_smarty->display($this->_name);
		}else{
			
			$this->_smarty->display($this->_name,$this->_cacheid);
		}
	}
	/**
	 * 返回渲染实体html
	 */
	function fetch()
	{
		foreach ((array) $this->_vars as $key => $value) {
			$this->_smarty->assign($key , $value);
		}
		if(empty($this->_cacheid)) {
			return $this->_smarty->fetch($this->_name);
		}else{
			return $this->_smarty->fetch($this->_name,$this->_cacheid);
		}
	}
	
	/**
	 * 设置缓存
	 * @param string $cache_lifetime	缓存生命 周期
	 * @param string $cacheid			缓存ID
	 */
	function setCache($cache_lifetime,$cacheid = null)
	{
		$this->_smarty->caching = true;
		$this->_smarty->cache_lifetime = $cache_lifetime;
		$this->_cacheid = $cacheid;
	}
	/**
	 * 检查是否有缓存
	 * @param string $name		视图名称
	 * @param string $cacheid	指定缓存ID
	 */
	function isCached($name,$cacheid = null)
	{
		if (!empty($cacheid)) $this->_cacheid = $cacheid;
		
		if(empty($this->_cacheid)) {
			return $this->_smarty->is_cached($name.'.tpl');
		}else{
			return $this->_smarty->is_cached($name.'.tpl', $this->_cacheid);
		}
	}
	/**
	 * 清除缓存
	 * @param string $name		视图名称
	 * @param string $cacheid	指定缓存ID
	 */
	function clearCache($name, $cacheid = null)
	{
		if (empty($cacheid)) $cacheid = $this->_cacheid;
		
		if(empty($cacheid)) {
			$this->_smarty->clear_cache($name.'.tpl');
		}else{
			$this->_smarty->clear_cache($name.'.tpl', $cacheid);
		}
	}
	function callFunction($callback,$param_arr)
	{
		return call_user_func_array(array($this->_smarty, $callback), $param_arr);
	}
}

