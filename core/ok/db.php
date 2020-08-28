<?php
/**
 * 定义 OK_DB 类
 *
 * @package database
 */

/**
 * 类 OK_DB 提供了 oko 访问数据库的基本接口。
 *
 * 类 OK_DB 提供的接口：
 *
 * -   定义数据库架构使用的常量；
 * -   为管理数据库连接提供了接口；
 * -   分析 DSN 的辅助方法。
 *
 * @package database
 */
abstract class OK_DB
{
    /**
     * OK_DB 数据库架构参数格式
     */
    // 问号作为参数占位符
    const PARAM_QM          = '?';
    // 冒号开始的命名参数
    const PARAM_CL_NAMED    = ':';
    // $符号开始的序列
    const PARAM_DL_SEQUENCE = '$';
    // @开始的命名参数
    const PARAM_AT_NAMED    = '@';

    /**
     * OK_DB 数据库架构查询结果返回格式
     */
    // 返回的每一个记录就是一个索引数组
    const FETCH_MODE_ARRAY  = 1;
    // 返回的每一个记录就是一个以字段名作为键名的数组
    const FETCH_MODE_ASSOC  = 2;

    /**
     * OK_DB 数据库关联模式
     */
    // 一对一关联
    const HAS_ONE       = 'has_one';
    // 一对多关联
    const HAS_MANY      = 'has_many';
    // 从属关联
    const BELONGS_TO    = 'belongs_to';
    // 多对多关联
    const MANY_TO_MANY  = 'many_to_many';

    /**
     * OK_DB 数据库架构字段和属性名映射
     */
    // 字段
    const FIELD = 'field';
    // 属性
    const PROP  = 'prop';
    
    // 设置关闭数据库的主从读写分离模式
    const DB_CLOSE_RW = 'db_close_rw';
    // 数据库主从读写分离模式
    const DB_RW_SEPARATE = 'db_rw_separate';
    // 多数据库集群分流模式
    const DB_SPLIT_STREAM = 'db_split_stream';

    /**
     * 字段元类型
     */

    /**
     * 获得一个数据库连接对象
     *
     * $dsn_name 参数指定要使用应用程序设置中的哪一个项目作为创建数据库连接的 DSN 信息。
     * 对于同样的 DSN 信息，只会返回一个数据库连接对象。
     *
     * 所有的数据库连接信息都存储在应用程序设置 db_dsn_pool 中。
     * 默认的数据库连接信息存储为 db_dsn_pool/default。
     *
     * @code php
     * // 获得默认数据库连接对应的数据库访问对象
     * $dbo = OK_DB::getConn();
     *
     * // 获得数据库连接信息 db_dsn_pool/news_db 对应的数据库访问对象
     * $dbo_news = OK_DB::getConn('news_db');
     * @endcode
     *
     * @param string $dsn_name 要使用的数据库连接
     *
     * @return OK_Adapter_Abstract 数据库访问对象
     */
    static function getConn($dsn_name = null)
    {
        $default = empty($dsn_name);
        if ($default && OK::isRegistered('dbo_default'))
        {
            return OK::registry('dbo_default');
        }

        if (empty($dsn_name))
        {
            $dsn = OK::ini('db_dsn_pool/default');
        }
        else
        {
            $dsn = OK::ini('db_dsn_pool/' . $dsn_name);
        }

        if (!empty($dsn['_use']))
        {
            $used_dsn = OK::ini("db_dsn_pool/{$dsn['_use']}");
            $dsn = array_merge($dsn, $used_dsn);
            unset($dsn['_use']);
            if ($dsn_name && !empty($dsn))
            {
                OK::replaceIni("db_dsn_pool/{$dsn_name}", $dsn);
            }
        }

        if (empty($dsn))
        {
            // LC_MSG: Invalid DSN.
            trigger_error('invalid dsn');
            throw new OK_Exception(__('Invalid DSN.'));
        }

        $dbtype = $dsn['driver'];
        $objid = "dbo_{$dbtype}_" .  md5(serialize($dsn));
        if (OK::isRegistered($objid))
        {
            return OK::registry($objid);
        }

        $class_name = 'OK_Adapter_' . ucfirst($dbtype);
        $dbo = new $class_name($dsn, $objid);

        OK::register($dbo, $objid);
        if ($default)
        {
            OK::register($dbo, 'dbo_default');
        }
        return $dbo;
    }

    /**
     * 将字符串形式的 DSN 转换为数组
     *
     * @code php
     * $string = 'mysql://root:mypass@localhost/test';
     * $dsn = OK_DB::parseDSN($string);
     * // 输出
     * // array(
     * //     driver:   mysql
     * //     host:     localhost
     * //     login:    root
     * //     password: mypass
     * //     database: test
     * //     port:
     * // )
     * @endcode
     *
     * @param string $dsn 要分析的 DSN 字符串
     *
     * @return array 分析后的数据库连接信息
     */
    static function parseDSN($dsn)
    {
        $dsn = str_replace('@/', '@localhost/', $dsn);
        $parse = parse_url($dsn);
        if (empty($parse['scheme']))
        {
            return false;
        }

        $dsn = array();
        $dsn['host']     = isset($parse['host']) ? $parse['host'] : 'localhost';
        $dsn['port']     = isset($parse['port']) ? $parse['port'] : '';
        $dsn['login']    = isset($parse['user']) ? $parse['user'] : '';
        $dsn['password'] = isset($parse['pass']) ? $parse['pass'] : '';
        $dsn['driver']   = isset($parse['scheme']) ? strtolower($parse['scheme']) : '';
        $dsn['database'] = isset($parse['path']) ? substr($parse['path'], 1) : '';

        return $dsn;
    }
}

