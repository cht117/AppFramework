<?php 
class API_Filter_App_Format extends API_Filter_Abstract
{
	private $charset;
	
	function execute()
	{
		$result = $this->context->result;
		if (!is_array($result))
		{
			$result = (!empty($result)) ? array('body'=>$result) : array();
		}
		//设定编码
		$charset = empty($this->context->apiConfig['API']['charset']) ? 'utf-8' :  strtolower($this->context->apiConfig['API']['charset']);
		if ($charset != 'utf-8')
		{
			$result = $this->formatArray($result , $charset);
		}
		
		$format = (empty($this->context->format)) ? 'json' : strtolower($this->context->format);
		$udi = $this->context->requestUDI();
		$data_key = implode('_', array($udi['controller'] , $udi['action'] , 'response'));
		
		switch ($format) 
		{
			case 'string':
				$result = (string)$result['body'];
				break;
			case 'csv':
				break;
			case 'xml':
				@header("Content-Type: text/xml;charset=utf-8");
				$result = Helper_Array::toXml($result, 'root');
				break;
			case 'php':
				@header("Content-Type: text/javascript;charset=utf-8");
				$result = serialize($result);
				break;
			case 'json':
				@header("Content-Type: text/javascript;charset=utf-8");
				$result = json_encode($result);
				break;
			case 'jsonp':
				//解决ajax跨域获取数据问题
				@header("Content-Type: text/javascript;charset=utf-8");
				if (!empty($_GET['jsoncallback']))
				{
					$result = $_GET['jsoncallback'] .'('. json_encode($result).')';
				}else {
					$result = '';
				}
				
				break;
				
			//case :
			default:
				$headerInfo = "HTTP/1.1 400 Unsupported parameter";
				Logger::getLogger('oop.debug.h400')->debug($headerInfo);
				header($headerInfo);
				exit();
				
		}
		
		$this->context->result = $result;
		
	}
	
	/**
	 * 格式化数据显示
	 *
	 * @param array $array
	 * @return array
	 */
	private function formatArray(array $array , $charset = 'GBK' )
	{
		return @eval("return ".iconv($charset,'UTF-8//IGNORE', var_export($array,1) ).';' );
	}
	
	
}
