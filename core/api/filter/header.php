<?php
/**
 * Header 过滤器 设置缓存或其他头部信息,目前只设置缓存
 * 
 * 'API_Filter_Header'	=> array('lifeTime' => 600),
 * 
 * @author Fidoo
 *
 */
class API_Filter_Header extends API_Filter_Abstract
{
	function execute()
	{
		
		if (isset($this->config['lifeTime']) && $this->config['lifeTime'] > 0)
		{
	        $lifeTime = &$this->config['lifeTime'];
	        header("Cache-Control: max-age=$lifeTime ,must-revalidate");
	    	header('Pragma:');
	    	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
	    	header("Expires: " .gmdate ('D, d M Y H:i:s', time() + $lifeTime). ' GMT');
		}else {
			header( "Expires: Mon, 26 Jul 1997 08:00:00 GMT "); 
			header( "Last-Modified: ".gmdate( "D, d M Y H:i:s ")."GMT "); 
			header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate ");
			header("Pragma: no-cache ");
		}
	}
}