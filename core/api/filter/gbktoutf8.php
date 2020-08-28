<?php
class API_Filter_GbkToUtf8 extends API_Filter_Abstract
{
	function execute()
	{
		$_POST = $this->format($_POST);
		$_GET = $this->format($_GET);
	}
	
	
	public function format($array)
	{
		return @eval("return ".iconv('gbk','UTF-8//IGNORE', var_export($array,1) ).';' );
	}
}
