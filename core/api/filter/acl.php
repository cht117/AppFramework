<?php
class API_Filter_ACl extends API_Filter_Abstract
{
	function execute()
	{
		// 获取 acl 权限表
		if (empty($this->context->acl)) 
		{
			//载入权限验证表
			$this->context->acl = include($this->context->system('USERACL_PATH'));
		}
		
		$method = strtolower($this->context->get('method'));
		$acl = & $this->context->acl;
		$app_key = & $this->context->app_data['app_key'];
		$inAcl = (!empty($acl[$method]) && is_array($acl[$method])) ? $acl[$method] : array();
		$access = in_array($app_key, $inAcl);
		//检查该请求是否允许访问
		if ($access == false) {
			// 返回错误码 403 被禁止访问
			$headerInfo = "HTTP/1.1 403 FORBIDDEN";
			Logger::getLogger('oop.debug')->perf('h403')->debug($headerInfo .' : ACL Access Denied');
			header($headerInfo);
			exit();
		}	
	}
}
