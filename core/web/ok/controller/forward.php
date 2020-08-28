<?php
/**
 * 定义 OK_Controller_Forward 类
 *
 * @package mvc
 */

/**
 * OK_Controller_Forward 将请求转发到另一个控制器动作执行
 *
 * @package mvc
 */
class OK_Controller_Forward
{
    /**
     * 附加参数
     *
     * @var array
     */
    public $args;

    /**
     * 构造函数
     *
     * @param string|array $udi
     * @param array $args
     */
    function __construct($udi, array $args = array())
    {
        OK_Context::instance()->changeRequestUDI($udi);
        $this->args = $args;
    }
}

