<?php
/**
 * 框架入口
 * 
 * 
 */
//! 加载性能剖析扩展
//require dirname(__FILE__) . '/libs/xhprof/xhprof.php';
//$G_XHPROF_OBJECT = new OkoXhprof();

//计算页面加载时间
global $G_FRAMEWORK_START_TIME;
$G_FRAMEWORK_START_TIME = microtime(1);


// 定义澳客代码根目录
//define('OKOOO_CODE_DIR', dirname(dirname(__FILE__)));
define('APP_FRAMEWORK_DIR', dirname(__FILE__));

//! 载入核心库
require dirname(__FILE__). '/core/ok.php';

//! 定义类加载路径
$G_APPCLASS_FILES = array(
	// 基础服务核心类
	//'oae_service'		=> OKOOO_CODE_DIR . '/Service/service.php',
	'logger'			=> APP_FRAMEWORK_DIR . '/libs/log4php/php/Logger.php',
);

//新的代码

