<?php
/**
 * 定义 OK_Result_Mssql 类
 *
 * @package database
 */

/**
 * OK_Result_Mssql 封装了一个 mssql 查询句柄，便于释放资源
 *
 * @package database
 */

class OK_Result_Mssql extends OK_Result_Abstract{
	function free()
	{
		if ($this->_handle) { mssql_free_result($this->_handle); }
		$this->_handle = null;
	}
	
	function fetchRow()
	{
		if ($this->fetch_mode == OK_DB::FETCH_MODE_ASSOC) 
		{
			OK::loadClass('Helper_Util');
		    $row = mssql_fetch_assoc($this->_handle);           
		    $row = Helper_Util::autoCharset($row,'gbk','utf-8');
			
			if ($this->result_field_name_lower && $row)
			{
				return array_change_key_case($row, CASE_LOWER);
			} 
			else 
			{
				return $row;
			}
		} 
		else 
		{
			return mssql_fetch_array($this->_handle);
		}
	}
}
?>