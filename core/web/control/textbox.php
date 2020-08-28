<?php
/**
 * 定义 Control_Textbox 类
 *
 * @package webcontrols
 */

/**
 * 单行文本框
 *
 * @package webcontrols
 */
class Control_Textbox extends Control_Input_Abstract
{
	function render()
	{
		return $this->_make('text');
	}
}

