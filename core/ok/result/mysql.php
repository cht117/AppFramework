<?php
/**
 * 定义 OK_Result_Mysql 类
 *
 * @package database
 */

/**
 * OK_Result_Mysql 封装了一个 mysql 查询句柄，便于释放资源
 *
 * @package database
 */
class OK_Result_Mysql extends OK_Result_Abstract
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
			$this->dolog($row);
			if ($this->result_field_name_lower && $row)
			{
				return array_change_key_case($row, CASE_LOWER);
			} else {
				return $row;
			}
		} else {
			$row =  mysql_fetch_array($this->_handle);
			$this->dolog($row);
			return $row;
		}
	}
	/**
	 * 数据库查询结果集是否写入日志
	 * @param array $row
	 */
	function dolog(& $row)
	{	
		if (OK_Adapter_Abstract::getLogEnabled() && is_array($row)) {
				// 取得结果集中行的数目
				Helper_DbLog::num(mysql_num_rows($this->_handle));
				Helper_DbLog::dump();
		}
	}
	
}

