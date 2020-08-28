<?php
/**
 * 定义 OK_ClassNotDefinedException 异常
 *
 * @package exception
 */

/**
 * OK_ClassNotDefinedException 异常指示指定的文件中没有定义需要的类
 *
 * @package exception
 */
class OK_ClassNotDefinedException extends OK_Exception
{
    public $class_name;
    public $filename;

    function __construct($class_name, $filename)
    {
        $this->class_name = $class_name;
        $this->filename = $filename;
        parent::__construct(__('Class "%s" not defined in file "%s".', $class_name, $filename));
    }
}

