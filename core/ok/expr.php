<?php
/**
 * 定义 OK_Expr 类
 *
 * @package database
 */

/**
 * OK_Expr 封装一个表达式
 *
 * @package database
 */
class OK_Expr
{
	/**
	 * 封装的表达式
	 *
	 * @var string
	 */
	protected $_expr;

	/**
	 * 构造函数
	 *
	 * @param string $expr
	 */
	function __construct($expr)
	{
		$this->_expr = $expr;
	}

	/**
	 * 返回表达式的字符串表示
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->_expr;
	}

	/**
	 * 格式化为字符串
	 *
	 * @param OK_DB_Adapter_Abstract $conn
	 * @param string $table_name
	 * @param array $mapping
	 * @param callback $callback
	 *
	 * @return string
	 */
	function formatToString($conn, $table_name = null, array $mapping = null, $callback = null)
	{
		if (!is_array($mapping)) {
			$mapping = array();
		}
		return $conn->qsql($this->_expr, $table_name, $mapping, $callback);
	}
}

