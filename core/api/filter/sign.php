<?php
class API_Filter_Sign extends API_Filter_Abstract
{
	
	function execute()
	{

		$sign1 = &$this->context->cookie('sign');
		
		//取app_key的密钥
		$app_data = & $this->context->app_data;
		
		if (empty($app_data) && !is_array($app_data))
		{
			// 返回错误码 401 未授权 , 失效或者非法的token
			$headerInfo = "HTTP/1.1 401 Invalid signature";
			Logger::getLogger('oop.debug')->perf('h401')->debug($headerInfo);
			header($headerInfo);
			exit();
		}
		
		$sign2 = $this->generateSign($app_data['app_secret'], array_merge($this->context->post(),$this->context->get()));
		if ($sign1 != $sign2)
		{
			// 返回错误码 401 未授权 , 失效或者非法的token
			$headerInfo = "HTTP/1.1 401 Invalid signature";
			Logger::getLogger('oop.debug.h401')->debug($headerInfo.' : sign : ' . json_encode(array($sign1,$sign2)));
			header($headerInfo);
			exit();
		}
	}
	
	/**
	 * 签名函数
	 * 
	 * @param string $appSecret	客户端密钥
	 * @param string $paramArr	请求参数
	 */
	function generateSign($appSecret, $paramArr)
	{
		ksort($paramArr);
		$stringToBeSigned = $appSecret;
		foreach ($paramArr as $k => $v)
		{
			if("@" != substr($v, 0, 1))
			{
				$stringToBeSigned .= "$k$v";
			}
		}
		unset($k, $v);
		$stringToBeSigned .= $appSecret;
		//Logger::getLogger('sign')->debug('sign string '.$stringToBeSigned);
		return strtoupper(md5($stringToBeSigned));
	}
		
	
}