<?php
/**
 * 业务应用执行器,调用 service 层
 * @author Administrator
 *
 */
class API_Worker_Amf extends API_Worker_Abstract
{
	
	
	function execute()
	{
		
		//  开始执行 service
		require 'gatewayinfo.php';
	}
	
	
}