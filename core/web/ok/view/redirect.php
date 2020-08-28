<?php
/**
 * 定义 OK_View_Redirect 类
 *
 * @package mvc
 */

/**
 * OK_View_Redirect 类封装了一个浏览器重定向操作
 *
 * @package mvc
 */
class OK_View_Redirect
{
    /**
     * 重定向 URL
     *
     * @var string
     */
    public $url;

    /**
     * 重定向延时（秒）
     *
     * @var int
     */
    public $delay;

    /**
     * 构造函数
     *
     * @param string $url
     * @param int $delay
     */
    function __construct($url, $delay = 0)
    {
        $this->url = $url;
        $this->delay = $delay;
    }

    /**
     * 执行
     */
    function execute()
    {
        $delay = (int)$this->delay;
        $url = $this->url;
        if ($delay > 0) {
            echo <<<EOT
<html>
<head>
<meta http-equiv="refresh" content="{$delay};URL={$url}" />
</head>
</html>
EOT;
        } else {
            header("Location: {$url}");
        }
        exit;
    }
}

