<?php
/**
 * 定义 OK_Exception 类
 *
 * @package core
 */

/**
 * OK_Exception 是 oko 所有异常的基础类
 *
 * @package core
 */
class OK_Exception extends Exception
{
    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param int $code 错误代码
     */
    function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * 输出异常的详细信息和调用堆栈
     *
     * @code php
     * OK_Exception::dump($ex);
     * @endcode
     */
    static function dump(Exception $ex)
    {
    	global $G_FRAMEWORK_START_TIME;
    	
        $out = "exception '" . get_class($ex) . "'";
        if ($ex->getMessage() != '')
        {
            $out .= " with message '" . $ex->getMessage() . "'";
        }
        
        $frameworkRunTime = microtime(1) - $G_FRAMEWORK_START_TIME;
        $out .= " framework-run-time {$frameworkRunTime}";
		
        $out .= ' in ' . $ex->getFile() . ':' . $ex->getLine() . "\n\n";
        
        if (OK::ini('error_display_source')) {
        	$out .= $ex->getTraceAsString();
        }
        /*
        $traceAsString = '';
        foreach ($ex->getTrace() as $key => $value)
        {
        	$traceAsString .= "\n#{$key} {$value['file']}({$value['line']}): {$value['class']}{$value['type']}(";
        	if (count($value['args']) > 0) {
        		//$traceAsString .= '\'' . implode(',', $value['args'] . '\'');
        		$traceAsString .= implode(',', $value['args']);
        	}
        	$traceAsString .= ')';
        }
        */
        
        //! 回调异常附加操作
        if (method_exists($ex, 'additional')) {
        	$ex->additional();
        }
        
        if (OK::ini('error_display_logfile')) {
        	Logger::getLogger('system.exception.dump')->error($out);
        	Logger::getLogger('system.exception.cep')->apps('system.exception.cep')->error(json_encode(array(
        	    'type'	=> get_class($ex)
        	)));
        	//返回500错误
        	//ob_start();
        	//header("HTTP/1.1 506 Service Exception" , true,true);
        }
        if (OK::ini('error_display')) {
	        if (ini_get('html_errors')) {
	            echo nl2br(htmlspecialchars($out));
	        }else {
	            echo $out;
	        }
    	}
    }
    /**
     * 异常附加操作
     */
    function additional()
    {
    	
    }
}

