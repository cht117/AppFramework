<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * 杯赛轮次数字换为汉字
 *
 * Type:     function<br>
 * Name:     round_chinese<br>
 * @author   chenp@okooo.net
 * @param int
 * @return string
 */
function smarty_modifier_round_chinese($round)
{
	return iconv('utf-8','gbk',Helper_Tool::convertRound($round));
}

/* vim: set expandtab: */

?>
