<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * �����ִ����ֻ�Ϊ����
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
