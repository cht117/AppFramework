<?php
/**
 * 定义 Control_Input_Abstract 类
 *
 * @package webcontrols
 */

/**
 * Control_Input_Abstract 类使所有输入框控件的基础类
 *
 * @package webcontrols
 */
abstract class Control_Input_Abstract extends OK_UI_Control_Abstract
{
	protected function _make($type)
	{
		$out = "<input type=\"{$type}\" ";
        $out .= $this->_printIdAndName();
        $out .= $this->_printValue();
		$out .= $this->_printAttrs();
        $out .= $this->_printDisabled();
        $out .= '/>';

        return $out;
	}
}

