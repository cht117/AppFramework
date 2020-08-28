<?php
/**
 * 定义 OK_ActiveRecord_CallToUndefinedMethodException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_CallToUndefinedMethodException 异常指示未定义的方法
 *
 * @package exception
 */
class OK_ActiveRecord_CallToUndefinedMethodException extends OK_ActiveRecord_Exception
{
    public $method_name;

    function __construct($class_name, $method_name)
    {
        $this->method_name = $method_name;
        // LC_MSG: Call to undefined method "%s" on object "%s" instance.
        parent::__construct($class_name, __('Call to undefined method "%s" on object "%s" instance.', $method_name, $class_name));
    }
}

