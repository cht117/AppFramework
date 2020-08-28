<?php
/**
 * 定义 Control_DropdownList 类
 *
 * @package webcontrols
 */

/**
 * Control_DropdownList 构造一个下拉列表框
 *
 * @package webcontrols
 */
class Control_DropdownList extends QUI_Control_Abstract
{
	function render()
	{
        $selected = $this->_extract('selected');
        $value    = $this->_extract('value');
		$items    = $this->_extract('items');

        if (strlen($value) && strlen($selected) == 0)
        {
            $selected = $value;
        }

		$out = '<select ';
		$out .= $this->_printIdAndName();
		$out .= $this->_printDisabled();
		$out .= $this->_printAttrs();
		$out .= ">\n";

        foreach ((array)$items as $value => $caption)
        {
			$out .= '<option value="' . htmlspecialchars($value) . '" ';
            if ($value == $selected && strlen($value) == strlen($selected))
            {
                $out .= 'selected="selected" ';
            }
			$out .= '>';
			$out .= htmlspecialchars($caption);
			$out .= "</option>\n";
		}
		$out .= "</select>\n";

        return $out;
	}
}

