<?php
/**
 * 定义 OK_Cache_Exception 异常
 *
 * @package exception
 */

/**
 * OK_Cache_Exception 异常封装所有的缓存错误
 *
 * @package exception
 */
class OK_Cache_Exception extends OK_Exception
{
    public $filename;

    function __construct($msg, $filename = null)
    {
        $this->filename = $filename;
        parent::__construct(__($msg, $filename));
    }
}

