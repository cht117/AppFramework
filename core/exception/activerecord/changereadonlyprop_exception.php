<?php
/**
 * 定义 OK_ActiveRecord_ChangingReadonlyPropException 类
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_ChangingReadonlyPropException 指示某个属性是只读
 *
 * @package exception
 */
class OK_ActiveRecord_ChangingReadonlyPropException extends OK_ActiveRecord_Exception
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Setting readonly property "%s" on object "%s" instance.
        parent::__construct($class_name, __('Setting readonly property "%s" on object "%s" instance.', $prop_name, $class_name));
    }
}

