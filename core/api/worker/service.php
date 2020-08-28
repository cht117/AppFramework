<?php
/**
 * 业务应用执行器,调用 service 层
 * @author Administrator
 *
 */
class API_Worker_Service extends API_Worker_Abstract
{
	
	
	function execute()
	{
		//  开始执行 service
		$service = Framework_Service::find($this->context->apiConfig['API']['module'], $this->context->apiConfig['API']['class']);
		$this->context->result = call_user_func_array(array($service, $this->context->apiConfig['API']['function']), $this->getArgs());
	}
	
	
}