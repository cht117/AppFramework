<?php
class OAE_Service
{
	private static $instances = null;
	
	static function find($module, $name, $version = 0)
	{
		$module = strtolower($module);
		$name = strtolower($name);
		
		if (empty(self::$instances[$module][$name][$version]))
		{
			//初始化
			self::$instances[$module][$name][$version] = new self($module, $name, $version);
		}
		return self::$instances[$module][$name][$version];
	}
	private $_oae_config = array(
		//网关地址
		'gateway_url'		=> 'http://product.oae.okooo.com/oae.php',
		//app 唯一序列号
		'app_key'			=> '',
		//私钥
		'app_secret'		=> '',
		//返回格式定义
		'format'			=> 'php',
		//全局版本设定.默认是稳定版本,默认值 0
		'version'			=> 0,
		//服务模块
		'module'			=> '',
		//服务名称
		'name'				=> '',
		//读超时,默认为0
		'read_timeout'		=> 0,
		//连接超时
		'connect_timeout'	=> 0,
	);
	
	private function __construct($module, $name, $version)
	{
		$this->_oae_config['module'] = $module;
		$this->_oae_config['name'] = $name;
		
		
		$config = OK::ini('app_engine');
		if (!empty($config['app_key'])) {
			$this->_oae_config['app_key'] = $config['app_key'];
		}
		if (!empty($config['app_secret'])) {
			$this->_oae_config['app_secret'] = $config['app_secret'];
		}
		if (!empty($config['gateway_url'])) {
			$this->_oae_config['gateway_url'] = $config['gateway_url'];
		}
		if (!empty($config['version'])) {
			$this->_oae_config['version'] = $config['version'];
		}
		if (!$version > 0) {
			$this->_oae_config['version'] = $version;
		}
		if (!empty($config['format'])) {
			$this->_oae_config['format'] = $config['format'];
		}
		if (!empty($config['read_timeout'])) {
			$this->_oae_config['read_timeout'] = $config['read_timeout'];
		}
		if (!empty($config['connect_timeout'])) {
			$this->_oae_config['connect_timeout'] = $config['connect_timeout'];
		}
	}
	
	function __call($method, $args)
	{
		$apiParams = array(
			'app_key'		=> $this->_oae_config['app_key'],
			'app_secret'	=> $this->_oae_config['app_secret'],
			'format'		=> $this->_oae_config['format'],
			'service'		=> array(
				'module'	=> $this->_oae_config['module'],
				'name'		=> $this->_oae_config['name'],
				'version'	=> $this->_oae_config['version'],
				'method'	=> $method,
				'args'		=> $args,
			),
		);
		$apiParams = serialize($apiParams);
		try
		{
			$url_method = "{$this->_oae_config['module']}_{$this->_oae_config['name']}::{$method}";
			$resp = $this->__curl($this->_oae_config['gateway_url'] . "?method={$url_method}", array('body' => $apiParams));
		}catch (Exception $e) {
			$code = $e->getCode();
			$msg = $e->getMessage();
			$result = array($code,$msg);
			pprint($result);
			return '';
		}
		switch ($this->_oae_config['format'])
		{
			case 'php':
				$resp = unserialize($resp);
				break;
		}
		return $resp['response'];
		
	}
	private function __curl($url, $postFields = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->_oae_config['read_timeout']) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->_oae_config['read_timeout']);
		}
		if ($this->_oae_config['connect_timeout']) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_oae_config['connect_timeout']);
		}
		//https 请求
		if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
	
		if (is_array($postFields) && 0 < count($postFields))
		{
			$postBodyString = "";
			$postMultipart = false;
			foreach ($postFields as $k => $v)
			{
				if("@" != substr($v, 0, 1))//判断是不是文件上传
				{
					$postBodyString .= "$k=" . urlencode($v) . "&";
				}
				else//文件上传用multipart/form-data，否则用www-form-urlencoded
				{
					$postMultipart = true;
				}
			}
			unset($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postMultipart)
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			}
			else
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
			}
		}
		$reponse = curl_exec($ch);
	
		if (curl_errno($ch))
		{
			throw new Exception(curl_error($ch),0);
		}
		else
		{
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode)
			{
				throw new Exception($reponse,$httpStatusCode);
			}
		}
		curl_close($ch);
		return $reponse;
	}
}