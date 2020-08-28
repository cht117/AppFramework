<?php
/**
 * 定义 OK_Result_Cassandra 类
 *
 * @package database
 */

/**
 * OK_Result_Cassandra 封装了一个 mysql 查询句柄，便于释放资源
 *
 * @package database
 */
class OK_Result_Cassandra extends OK_Result_Abstract
{
	function free()
	{
		if ($this->_handle) { mysql_free_result($this->_handle); }
		$this->_handle = null;
	}

	function fetchRow()
	{
		if ($this->fetch_mode == OK_DB::FETCH_MODE_ASSOC) {
			$row = mysql_fetch_assoc($this->_handle);
			if ($this->result_field_name_lower && $row)
			{
				return array_change_key_case($row, CASE_LOWER);
			} else {
				return $row;
			}
		} else {
			return mysql_fetch_array($this->_handle);
		}
	}
}

