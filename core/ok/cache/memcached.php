<?php
/**
 * OK_Cache_Memcache 使用 pecl-memcached 扩展来缓存数据
 * 
 * @package cache
 */
class OK_Cache_Memcached
{
    /**
     * memcached连接句柄
     *
     * @var resource
     */
    protected $_conn;

    /**
     * 默认的缓存服务器
     *
     * @var array
     */
    protected $_default_server = array(
        /**
         * 缓存服务器地址或主机名
         */
        'host' => '192.168.8.218',

        /**
         * 缓存服务器端口
         */
        'port' => '11211',

        /**
         * 权重
         */
        'weight' => 100,
    );

    /**
     * 默认的缓存策略
     *
     * @var array
     */
    protected $_default_policy = array(
        /**
         * 缓存服务器配置，参看$_default_server
         * 允许多个缓存服务器
         */
        'servers' => array(),

        /**
         * 是否压缩缓存数据
         */
        'compressed' => false,

        /**
         * 缓存有效时间
         *
         * 如果设置为 0 表示缓存永不过期
         */
        'life_time' => 900,
    );

    /**
     * 构造函数
     *
     * @param 缓存策略 $policy
     */
    public function __construct(array $policy = array()) {
        if (!extension_loaded('memcached')) {
            throw new OK_Cache_Exception('The pecl-memcached extension must be loaded before use!');
        }

        $policy = array_merge($this->_default_policy, $policy);

        if (empty($policy['servers'])) {
            $policy['servers'][] = $this->_default_server;
        }

        $this->_conn = new Memcached();
        $servers = array();
        foreach ($policy['servers'] as $server) {
            $servers[] = array($server['host'], $server['port'], $server['weight']);
        }
        if (!$result = $this->_conn->addServers($servers)) {
            throw new OK_Cache_Exception(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
        }

        $this->_conn->setOption(Memcached::OPT_COMPRESSION, (boolean)$policy['compressed']);

        $this->_default_policy = $policy;
    }

    /**
     * 写入缓存
     *
     * [code]
     * $memcache->set('key', 'value', 3600);
     * $memcache->set(array('key1' => 'value1', 'key2' => 'value2'), 3600);
     * [/code]
     *
     * @param string $id
     * @param mixed $data
     * @param array $policy
     * @return boolean
     */
    public function set() {
        $args = func_get_args();
        if (is_array($args[0])) {
            $items = $args[0];
            $life_time = isset($args[1]) ? $args[1] : $this->_default_policy['life_time'];
            $expire_time = time() + (int)$life_time;
            return $this->_conn->setMulti($items, $expire_time);
        } else {
            $key = $args[0];
            $value = $args[1];
            $life_time = isset($args[2]) ? $args[2] : $this->_default_policy['life_time'];
            $expire_time = time() + (int)$life_time;
            return $this->_conn->set($key, $value, $expire_time);
        }

        throw new OK_Exception('Invalid arguments');
    }

    /**
     * 读取缓存，失败或缓存撒失效时返回 false
     *
     * [code]
     * $cache->get('key');
     * $cache->get(array('key1', 'key2'));
     * [/code]
     *
     * @param string $id
     * @return mixed
     */
    public function get($id) {
        if (is_array($id)) {
            $result = array();
            $m = $this->_conn->getDelayed($id);
            while ($r = $m->fetch()) {
                $result[$r['key']] = $r['value'];
            }
            return $result;
        }
        return $this->_conn->get($id);
    }

    /**
     * 删除指定的缓存
     *
     * @param string $id
     * @return boolean
     */
    public function remove($id) {
        return $this->_conn->delete($id);
    }

    /**
     * 清除所有的缓存数据
     *
     * @return boolean
     */
    public function clean() {
        return $this->_conn->flush();
    }

    /**
     * 获得连接句柄
     * 
     * @access public
     * @return Memcached
     */
    public function getHandle() {
        return $this->_conn;
    }
}
