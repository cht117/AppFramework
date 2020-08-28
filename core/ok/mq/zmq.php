<?php
class OK_MQ_ZMQ extends OK_MQ_Abstract
{
	/**
	 * MQ服务Socket连接句柄
	 *
	 * @var ZMQSocket
	 */
	protected $_conn;
	
	public function __construct($dsn, $id)
	{
		parent::__construct($dsn, $id);
	}
	
	protected function connect()
    {
    	$this->_conn = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUB, $this->getID());
    	$dsn = $this->getDSN();
    	//$this->_conn->connect($dsn['host']);
		for ($n = 0; $n < 3; $n ++)
		{
			$endpoints = $this->_conn->getEndpoints();
			if (! in_array($dsn, $endpoints['connect']))
			{
				$this->_conn->connect($dsn['host']);
				usleep(3000);
			}else {
				break;
			}
		}
    }
    /**
     * 发送消息服务
     *
     * @todo 目前未实现同步模式
     * 
     * @param string $topic			队列名称，主题频道名称
     * @param array|string $msg		发送的消息内容
     * @param bool $isBackground	是否使用异步非阻塞方式
     */
    public function send($topic, $msg, $isBackground = true)
    {
    	if (!is_object($this->_conn)) {
    		$this->connect();
    	}
    	$dsn = $this->getDSN();
    	
		$this->_conn->send($topic, ZMQ::MODE_SNDMORE);
		
		$mode = ($isBackground == true) ? ZMQ::MODE_DONTWAIT : ZMQ::SOCKET_DEALER;
		
		switch ($dsn['format'])
		{
			case 'json':
				$msg = json_encode($msg);
				break;
			case 'php':
				$msg = serialize($msg);
				break;
			default:
				$msg = json_encode($msg);
		}
		
		$this->_conn->send($msg, $mode);
		
    }
    /**
     * 关闭连接
     * @see OK_MQ_Abstract::close()
     */
    public function close()
    {
    	if (is_object($this->_conn))
		{
			//防止持久化引发的问题,暂时不解除绑定
			//$dsn = $this->getDSN();
			//$this->_conn->unbind($dsn['host']);
		}
		parent::_clear();
    }

}