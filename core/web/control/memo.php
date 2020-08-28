<?php
/**
 * 定义 Control_Memo 类
 *
 * @package webcontrols
 */

/**
 * 构造一个多行文本框
 *
 * @package webcontrols
 */
class Control_Memo extends Ok_UI_Control_Abstract
{
	function render()
	{
		$value = $this->_extract('value');
		$out = '<textarea ';
		$out .= $this->_printIdAndName();
		$out .= $this->_printAttrs();
		$out .= $this->_printDisabled();
		$out .= '>';
		$out .= htmlspecialchars($value);
		$out .= '</textarea>';

        return $out;
	}
}

