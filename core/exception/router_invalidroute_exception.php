<?php

/**
 * 定义 OK_Router_InvalidRouteException 异常
 *
 * @package exception
 */

/**
 * OK_Router_InvalidRouteException 异常指示无效的路由规则
 *
 * @package exception
 */
class OK_Router_InvalidRouteException extends OK_Exception
{
    public $route_name;

    function __construct($route_name, $msg)
    {
        $this->route_name = $route_name;
        parent::__construct($msg);
    }
}

