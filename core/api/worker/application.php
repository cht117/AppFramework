<?php
/**
 * 针对 App 前端数据调用自定义接口处理 Worker
 * @author fidoo
 *
 */
class API_Worker_Application extends API_Worker_Abstract
{
	
	
	function execute()
	{
		$context = API_Core_Context::instance();
		$root_dir = $context->system('ROOT_DIR');
		$udi = $context->requestUDI('array');
		//
		$namespace = ($udi[OK_Context::UDI_NAMESPACE] == 'default') ? '' : $udi[OK_Context::UDI_NAMESPACE];
		if (empty($namespace)) {
			$class_name = implode('_', array('app',$udi[OK_Context::UDI_MODULE],$udi[OK_Context::UDI_CONTROLLER]));
		}else {
			$class_name = implode('_', array('app',$udi[OK_Context::UDI_MODULE],$udi[OK_Context::UDI_NAMESPACE],$udi[OK_Context::UDI_CONTROLLER]));
		}
		
		
		if (!class_exists($class_name, false)) {
			//加载基础类
			OK::loadClassFile('abstract.php', array("{$root_dir}/application"), 'App_System_Abstract');
			//加载控制器类
			$file_dir = "{$root_dir}/application/{$udi[OK_Context::UDI_MODULE]}/{$namespace}";
			OK::loadClassFile($udi[OK_Context::UDI_CONTROLLER] . '.php', array($file_dir), $class_name);
		}
		/* @var $controller App_System_Abstract */
		$controller = new $class_name($this);
		$action_name = $udi[OK_Context::UDI_ACTION];
		if ($controller->existsAction($action_name)) {
			$response = $controller->execute($action_name);
		}else {
			$response = $controller->_on_action_not_defined($action_name);
		}
		$this->context->result = $response;
	}
	
	
}