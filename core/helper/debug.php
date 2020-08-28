<?php
/**
 *	计算程序运行所消耗的时间
 * 
 * 	@author  ligh<ligh@okooo.net>
 * 	@version 
 * 	@modify  2012-06-19
 */
class Helper_Debug
{
	/**
	 * 获取时间 
	 *
	 * @return float
	 */
	static public function getTime()
	{
		$mtime = microtime (true);
		$mtime = explode (' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];

		return $mtime;
	}

	/**
	 * 获取开始时间 
	 *
	 * @return float
	 */
	static public function startTime()
	{
		return self::getTime(); 
	}

	/**
	 * 获取结束时间 
	 *
	 * @return float
	 */
	static public function endTime()
	{
		return self::getTime(); 
	}

	/**
	 * 获取总消耗时间
	 *
	 * @return float
	 */
	static public function totalSpendTime()
	{
		global $starter;
		$ender = self::endTime();
		$total_time = abs($ender - $starter) / 1000;

		return $total_time;
	}
}
