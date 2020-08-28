<?php
class API_Filter_DataACL extends API_Filter_Abstract
{
	
	function execute()
	{
        // 获取 acl 权限表
		if (empty($this->context->acl))
		{
			//载入权限验证表
			$this->context->acl = include($this->context->system('DATA_USER_ACL_PATH'));
		}
        $acl = $this->context->acl[$this->context->UserName];
        $access = md5($acl['user_name'].$acl['app_key']) == $this->context->UKey;
		if ($access == false) {
			// 返回错误码 403 被禁止访问
			$headerInfo = "HTTP/1.1 403 FORBIDDEN";
			header($headerInfo);
			exit();
		}	
	}
	
	
}
