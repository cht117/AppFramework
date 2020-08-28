<?php
/**
 *
 * @package log4php
 * @author yuanwz@okooo.net
 */

/**
 * FileAppender appends log events to a file.
 *
 * Configurable parameters for this appender are:
 * 
 * - layout             - Sets the layout class for this appender
 * - port               - Sets the port of the socket.
 * - remoteHost         - Sets the remote host
 * - append             - Sets if the appender should append to the end of the file or overwrite content ("true" or "false")
 *
 * 
 * @version $Revision: 1 $
 * @package log4php
 * @subpackage appenders
 */
class LoggerAppenderZeroMQ extends LoggerAppender {

	/**
	 * @var boolean if {@link $file} exists, appends events.
	 */
	private $append = true;
	
	/**
	 * log sender
	 * 
	 * @var ZMQSocket
	 */
	protected $sender;
	
	
	/**
	 * Target host. On how to define remote hostaname see 
	 * {@link PHP_MANUAL#fsockopen}
	 * @var string 
	 */
	private $remoteHost = '';
	
	/**
	 * @var integer the network port.
	 */
	private $port = 4446;
	
	/**
	 * @var bool
	 */
	private $noBlock = true;
	
	private $runZMQ = true;
	
	public function __construct($name = '') {
		parent::__construct($name);
		$this->requiresLayout = true;
		
		
		
	}

	public function __destruct() {
       $this->close();
   	}
   	
	public function activateOptions()
	{
		if (class_exists('ZMQSocket')) {
			$this->sender = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUB, 'logger');
			$dsn = 'tcp://' . $this->getRemoteHost() . ':' . $this->getPort();
			if (method_exists($this->sender, 'getEndpoints')) {
				for ($n = 0; $n < 3; $n++)
				{
					$endpoints = $this->sender->getEndpoints();
					if (! in_array($dsn, $endpoints['connect']))
					{
						$this->sender->connect($dsn);
						usleep(3000);
					}else {
						break;
					}
				}
			}else {
				$this->sender->connect($dsn);
			}
			
		}else {
			$this->runZMQ = false;
		}
	}
	
	public function close() {
		if($this->closed != true) {
			
			//
			
			$this->closed = true;
		}
	}

	public function append(LoggerLoggingEvent $event)
	{
		if ($this->runZMQ && $this->layout !== null)
		{
			//兼容OK基础框架
			if (class_exists(OK)) {
				//取配置文件中的app name
				$app_name = OK::ini('app_engine/app_name', 'app_engine');
			}else {
				$app_name = 'app_engine';
			}
			$logerName = $app_name . '.'. $event->getLoggerName();

			$this->sender->send($logerName,ZMQ::MODE_SNDMORE);
			if ($this->getNoBlock() == true) {
				$this->sender->send($this->layout->format($event), ZMQ::MODE_DONTWAIT);
			}else {
				$this->sender->send($this->layout->format($event));
			}
		}
	}
	
	
	/**
	 * @return boolean
	 */
	public function getAppend() {
		return $this->append;
	}

	public function setAppend($flag) {
		$this->append = LoggerOptionConverter::toBoolean($flag, true);		  
	}
	
	
	/**
	 * @param string
	 */
	public function setRemoteHost($hostname) {
		$this->remoteHost = $hostname;
	}
	
	/**
	 * @param integer
	 */
	public function setPort($port) {
		$port = LoggerOptionConverter::toInt($port, 0);
		if($port > 0 and $port < 65535) {
			$this->port = $port;	
		}
	}
	
	public function setNoBlock($value) {
		$this->noBlock = ($value == 'true') ? true : false;
	}
	
	/**
	 * @return string
	 */
	public function getHostname() {
		return $this->getRemoteHost();
	}
	public function getRemoteHost() {
		return $this->remoteHost;
	}
	/**
	 * @return integer
	 */
	public function getPort() {
		return $this->port;
	}
	/**
	 * @return bool
	 */
	public function getNoBlock() {
		return $this->noBlock;
	}
	 
}
