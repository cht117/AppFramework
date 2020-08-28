<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty count_characters modifier plugin
 *
 * Type:     modifier<br>
 * Name:     count_characteres<br>
 * Purpose:  count the number of characters in a text
 * @link http://smarty.php.net/manual/en/language.modifier.count.characters.php
 *          count_characters (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param boolean include whitespace in the character count
 * @return integer
 */
function smarty_modifier_arrcolor($code, $flag = 'arr')
{
	$r = null;
	switch($flag){
		case 'arr':
			if($code == 0){
				$r = '&uarr;';
			}elseif($code == 2){
				$r = '&darr;';
			}else{
				$r = '';
			}
			break;
		case 'color':
			if($code == 0){
				$r = 'redtxt';
			}elseif($code == 2){
				$r = 'bluetxt';
			}else{
				$r = '';
			}
			break;
		default:
	}

    return $r;
}

/* vim: set expandtab: */

?>
