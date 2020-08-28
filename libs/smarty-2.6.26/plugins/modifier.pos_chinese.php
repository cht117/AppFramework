<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * 亚盘值转换为汉字
 *
 * Type:     function<br>
 * Name:     handicap_chinese<br>
 * @author   gaodch@okooo.net
 * @param float
 * @return string
 */
function smarty_modifier_pos_chinese($pos)
{
	return iconv('utf-8','gbk',Helper_Tool::convertPos($pos));
}

/* vim: set expandtab: */

?>
