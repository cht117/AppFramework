<?php
class API_Filter_AccessToken extends API_Filter_Abstract
{
	
	function execute()
	{
		//dprint(API_Core_Context::instance());
		$access_token = $this->context->cookie('access_token');
		
		if (empty($access_token))
		{
			// 返回错误码 401 未授权 , 失效或者非法的token
			$headerInfo = "HTTP/1.1 401 Expired Token";
			Logger::getLogger('oop.debug')->perf('h401')->debug($headerInfo .' : access_token empty');
			header($headerInfo);
			exit();
		}
		/* @var $memcache OK_Cache_Memcache */
		//$memcache = OK::singleton('OK_Cache_Memcache');
		//session.save_path
		
		$policy['servers'] = OK::ini('open_api_ini/filter/accesstoken/memcache');
		if (isset($policy['servers']['host'])) {
			$policy['servers'] = array($policy['servers']);
		}
		
		$memcache = new OK_Cache_Memcache($policy);
		
		//$memcache->set($access_token, array('app_key'=>'test01','app_secret'=>'748150e435fe0cc93686a12d3'), array('life_time'=>0));
		$app_data = $memcache->get($access_token);
		if (empty($app_data) || !is_array($app_data))
		{
			// 返回错误码 401 未授权 , 失效或者非法的token
			$headerInfo = "HTTP/1.1 401 Expired Token";
			Logger::getLogger('oop.debug')->perf('h401')->debug($headerInfo.' : access_token_data : '.json_encode($app_data).' access_token : '.$access_token.' '.$_SERVER['REMOTE_ADDR']);
			header($headerInfo);
			exit();
		}
		
		//$memcache->set($access_token, $app_data, array('life_time'=>0));
		$this->context->app_data = $app_data;
	}
	
	
}
