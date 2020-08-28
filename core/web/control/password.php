<?php
/**
 * 定义 Control_Password 类
 *
 * @package webcontrols
 */

/**
 * 密码输入框
 *
 * @package webcontrols
 */
class Control_Password extends Control_Input_Abstract
{
	function render()
	{
		return $this->_make('password');
	}
}

