<?php
/**
 * 定义 OK_ActiveRecord_UndefinedPropException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_UndefinedPropException 异常指示未定义的属性
 *
 * @package exception
 */
class OK_ActiveRecord_UndefinedPropException extends OK_ActiveRecord_Exception
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Undefined property "%s" on object "%s" instance.
        parent::__construct($class_name, __('Undefined property "%s" on object "%s" instance.', $prop_name, $class_name));
    }
}

