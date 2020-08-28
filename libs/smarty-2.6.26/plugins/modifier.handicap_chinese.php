<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * ����ֵת��Ϊ����
 *
 * Type:     function<br>
 * Name:     handicap_chinese<br>
 * @author   gaodch@okooo.net
 * @param float
 * @return string
 */
function smarty_modifier_handicap_chinese($handicap)
{
	return iconv('utf-8','gbk',Helper_Tool::getHandicap($handicap));
}

/* vim: set expandtab: */

?>
