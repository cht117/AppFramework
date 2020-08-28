<?php
/**
 * 定义 OK_ActiveRecord_DestroyWithoutIdException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_DestroyWithoutIdException 指示视图删除一个没有主键值的对象
 *
 * @package exception
 */
class OK_ActiveRecord_DestroyWithoutIdException extends OK_ActiveRecord_Exception
{
    public $ar_object;

    function __construct(OK_ActiveRecord_Exception $object)
    {
        $this->ar_object = $object;
        $class_name = $object->getMeta()->class_name;
        // LC_MSG: Destroy object "%s" instance without ID.
        parent::__construct($class_name, __('Destroy object "%s" instance without ID.', $class_name));
    }
}

