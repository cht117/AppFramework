<?php
/**
 * 定义 OK_ActiveRecord_ExpectsAssocPropException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_ExpectsAssocPropException 异常指示对象的关联属性没有设置
 *
 * @package exception
 */
class OK_ActiveRecord_ExpectsAssocPropException extends OK_ActiveRecord_Exception
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Expects property "%s" on object "%s" instance for association operation.
        parent::__construct($class_name, __('Expects property "%s" on object "%s" instance for association operation.', $prop_name, $class_name));
    }
}

