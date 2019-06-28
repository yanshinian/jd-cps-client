<?php
/**
 * Created by PhpStorm.
 * User: yanshinian@yeah.net
 * Date: 2019/5/22
 * Time: 17:45
 *
 */

namespace Util;

include(APPLICATION_PATH . 'library/Jd/JdSdk.php');
class CpsClientUtil
{
    private static $instance ;
    private $jdClident;
    private $config;
    private $request;
    private $session = null;
    private $bestUrl = null;
    private $result;

    /**
     * jdClidentUtil constructor.
     * @desc 初始化配置
     * @param $config
     */
    private function __construct($config)
    {
        $this->config = $config;
        $jdClident = new \CpsClient($config['appkey'], $config['appsecret']);
        $jdClident->format = 'json';
        $this->jdClident = $jdClident;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 对数据源都封装成单例
     * @param array $config
     * @return jdClidentUtil
     */
    public static function getInstance($config = ['appkey' => '', 'appsecret' => ''])
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 设置本次使用的request 类
     * @param $requestName
     */
    public function setRequest($requestName)
    {
        $className = '\\' . $requestName;
        $this->request = new $className();
    }

    /**
     * @author yanshinian@yeah.net
     * @desc
     * @param $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 设置查询条件，命名为where 感觉更语义化
     * @param $condition
     * @throws \Exception
     */
    public function setCondition($condition)
    {
        if (!empty($condition)) {
            foreach ($condition as $index => $value) {
                $newIndex = str_replace('_', '', $index); // 为了书写直观，增加对下划线支持
                $condition[$newIndex] = $value;
                unset($condition[$index]);
                $method = 'set' . $newIndex; // 调用set方法，php不区分大小写
                if (is_callable([$this->request, $method])) {
                    call_user_func([$this->request, $method], $value);
                } else {
                    throw new \Exception(get_class($this->request) . ': unkown method ' . $method);
                }
            }
        }

        return $condition;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 调用的 client的执行方法
     * @return mixed|\ResultSet|\SimpleXMLElement
     */
    public function execute()
    {
        $this->result = $this->jdClident->execute($this->request, $this->session, $this->bestUrl);

        return $this->result;
    }
}
