<?php
/**
 * 定义 OK_ActiveRecord_Exception 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_Exception 封装所有与 ActiveRecord 有关的错误
 *
 * @package exception
 */
class OK_ActiveRecord_Exception extends OK_Exception
{
    /**
     * 相关的 ActiveRecord 类
     *
     * @var string
     */
    public $ar_class_name;

    function __construct($class_name, $msg)
    {
        $this->ar_class_name = $class_name;
        parent::__construct($msg);
    }
}

