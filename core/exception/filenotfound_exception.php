<?php
/**
 * 定义 OK_FileNotFoundException 异常
 *
 * @package exception
 */

/**
 * OK_FileNotFoundException 异常指示文件没有找到错误
 *
 * @package exception
 */
class OK_FileNotFoundException extends OK_Exception
{
    public $required_filename;

    function __construct($filename)
    {
        $this->required_filename = $filename;
        parent::__construct(__('File "%s" not found.', $filename));
    }
}
