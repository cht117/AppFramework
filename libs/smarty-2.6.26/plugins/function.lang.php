<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * 球队长短名获取
 * 
 * 
 * Type:     function<br>
 * Name:     toshort<br>
 * @author   gaohl@okooo.net
 * @param float
 * @return string
 */
function smarty_function_lang($params, &$smarty){
		
		static $nameList;
		static $langhandle;
		$langtype = $params['langType'] ? $params['langType'] : 'basketball_team';
		$flag = $params['aliasType'] ? $params['aliasType'] : 'short';
		$str = '';
		$Id = $params['Id'];
		$outCharset = $params['outCharset'] ? $params['outCharset'] : 'gbk';
		if($nameList[$langtype][$flag][$Id]){
	 		$str = $nameList[$langtype][$flag][$Id];
		}else{
	      	if(!$langhandle || !is_object($langhandle)){
				$langhandle = Framework_Service::find('system', 'translation');
            }
			$Name = $outCharset == 'gbk' ? iconv('gbk','utf-8',$params['Name']) : $params['Name'];
			$str = $langhandle->getOne($langtype, $flag, $Id);
			$str = $str ? $str : $Name;
 	        $nameList[$langtype][$flag][$Id] = $str;
		}
		return $outCharset == 'gbk' ? iconv('utf-8','gbk',$str) : $str;
}	
?>
