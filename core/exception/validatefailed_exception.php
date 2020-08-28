<?php
/**
 * 定义 OK_ActiveRecord_ValidateFailedException 异常
 *
 * @package exception
 */

/**
 * OK_ActiveRecord_ValidateFailedException 异常封装了 ActiveRecord 对象的验证失败事件
 *
 * @package exception
 */
class OK_ActiveRecord_ValidateFailedException extends OK_Validator_ValidateFailedException
{
    /**
     * 被验证的对象
     *
     * @var OK_ActiveRecord_Abstract
     */
    public $validate_obj;

    /**
     * 构造函数
     *
     * @param array $errors
     * @param OK_ActiveRecord_Abstract $obj
     */
    function __construct(array $errors, OK_ActiveRecord_Abstract $obj)
    {
        $this->validate_obj = $obj;
        parent::__construct($errors, $obj->toArray(0));
    }
}

