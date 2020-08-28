<?php
class API_Filter_UserSession extends API_Filter_Abstract
{
	function execute()
	{
		//session_start();
		if($_GET['sessid']) $_COOKIE['PHPSESSID'] = $_GET['sessid'];
		
        //不使用 GET/POST 变量方式
        ini_set('session.use_trans_sid',    0);
        //设置垃圾回收最大生存时间
        ini_set('session.gc_maxlifetime',   3600);
        //使用 COOKIE 保存 SESSION ID 的方式
        ini_set('session.use_cookies',      1);
        ini_set('session.cookie_path',      '/');
        //多主机共享保存 SESSION ID 的 COOKIE
        ini_set('session.cookie_domain',$this->getRootDomain());
        session_start();
		
	}
	/**
	 * 取得域名根
	 * - .okooo.com
	 */
	function getRootDomain()
	{
		$hostArray = explode('.', $_SERVER['HTTP_HOST']);
		$last = array_pop($hostArray);
if(strpos($last,':')) list($last,) = explode(':',$last);
		return '.'.array_pop($hostArray).'.'.$last;
	}
		
}
