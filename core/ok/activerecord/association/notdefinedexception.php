<?php
/**
 * 定义 OK_ActiveRecord_Association_NotDefinedException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_Association_NotDefinedException 异常指示未定义的关联
 *
 * @package exception
 */
class OK_ActiveRecord_Association_NotDefinedException extends OK_Exception
{
    /**
     * 相关的 ActiveRecord 类名称
     *
     * @var string
     */
    public $class_name;

    /**
     * 关联属性名
     *
     * @var string
     */
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->class_name = $class_name;
        $this->prop_name = $prop_name;
        // LC_MSG: ActiveRecord 类 "%s" 没有定义属性 "%s"，或者该属性不是关联对象.
        parent::__construct(__('ActiveRecord 类 "%s" 没有定义属性 "%s"，或者该属性不是关联对象.', $class_name, $prop_name));
    }
}

