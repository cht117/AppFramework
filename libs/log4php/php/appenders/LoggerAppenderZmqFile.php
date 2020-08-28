<?php
class LoggerAppenderZmqFile extends LoggerAppender 
{
	protected $dirname;
	
	public function __construct($name = '') 
	{
		parent::__construct($name);
		$this->requiresLayout = true;
	}

	public function __destruct() {
       $this->close();
   	}
   	
   	private $handles = array();
   	
   	private $run_handle_date = array();
   	
   	private function getHandle($loggerName)
   	{
   		$date = date('Y-m-d');
   		if (!empty($this->run_handle_date[$loggerName]) && $this->run_handle_date[$loggerName] != $date) {
   			fclose($this->handles[$loggerName]);
   			unset($this->handles[$loggerName]);
   		}
   		
   		if (!isset($this->handles[$loggerName]))
   		{
   			$fileName = $this->getDirname()."/$loggerName/".$this->getFileName();
   			
	   		if(!is_file($fileName)) {
				$dir = dirname($fileName);
				if(!is_dir($dir)) {
					mkdir($dir, 0777, true);
				}
			}
			
   			$this->handles[$loggerName] = fopen($fileName, 'a');
   			
   			$this->run_handle_date[$loggerName] = $date;
   			
   			if(!$this->handles[$loggerName] || !flock($this->handles[$loggerName], LOCK_EX)) {
   				return false;
   			}
   		}
   		
   		return $this->handles[$loggerName];
   	}
   	
   	
	public function activateOptions() 
	{
		
	}
	
	public function close() 
	{
		foreach ($this->handles as $key => &$fp)
		{
			fclose($fp);
		}
	}

	public function append(LoggerLoggingEvent $event) 
	{
		$fp = $this->getHandle($event->getLoggerName());
		if($fp and $this->layout !== null) {
			if(flock($fp, LOCK_EX)) {
				fwrite($fp, $this->layout->format($event));
				flock($fp, LOCK_UN);
			}
		} 
	}
	
	public function setDirname($dirname)
	{
		$this->dirname = $dirname;
	}
	
	public function getDirname()
	{
		return $this->dirname;
	}
	
	protected function getFileName()
	{
		static $time = false;
		if (!$time) $time = date('Ymd',time()).'.log';
		return $time;
	}

	 
}
