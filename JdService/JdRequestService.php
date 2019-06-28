<?php

/**
 * Created by PhpStorm.
 * User: yanshinian@yeah.net
 * Date: 2019/5/22
 * Time: 18:14
 *
 */

namespace JdService;

use Util\CpsClientUtil;

class JdRequestService
{
    protected $CpsClientUtil = null;
    protected $condition = [];
    protected $pageRule = '';
    protected $requestName = '';
    protected $fillable = [];
    private $result = [];
    private $callables = [];

    /**
     * JdRequestService constructor.
     * 初始化 CpsClientUtil，调用子类设置的requestName属性，设置request.
     */
    public function __construct()
    {
        $this->CpsClientUtil = CpsClientUtil::getInstance();
        $this->CpsClientUtil->setRequest($this->requestName);
        // 收集回调数组, 当然将来回调多了之后可以在子类中配置数组，在父类中对调用顺序排序。
        if (!empty($this->fillable)) {
            $this->callables[] = 'filterData';
        }
        if (!empty($this->pageRule)) {
            $this->callables[] = 'pageFormatData';
        }
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 设置查询条件
     *
     * @param $where
     *
     * @return static
     */
    public static function where($where)
    {
        $r = new static();
        $r->condition = $r->CpsClientUtil->setCondition($where);

        return $r;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc
     * @param $session
     * @return $this
     */
    public function session($session)
    {
        $this->CpsClientUtil->setSession($session);

        return $this;
    }
    /**
     * @author yanshinian@yeah.net
     * @desc 处理数据统一放这里
     *
     * @return array|mixed|\ResultSet|\SimpleXMLElement
     */
    public function Data()
    {

        $this->result = $this->CpsClientUtil->execute();
        if (isset($this->result['error_response'])) {
            return $this->result;
        }
        if (!empty($this->callables)) {
            foreach ($this->callables as $call) {
                call_user_func([$this, $call]);
            }
        }

        return $this->result;
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 对数据进行过滤
     */
    public function filterData()
    {
        $dealData = [];
        foreach ($this->fillable as $key => $value) {
            if (is_numeric($key)) {
                $dealData = &$this->result;
            } else {
                $keys = explode('.', $key);
                foreach ($keys as $index => $k) {
                    if ($index == 0) {
                        $dealData = &$this->result[$k];
                    } else {
                        $dealData = &$dealData[$k];
                    }
                    if (is_array($dealData)) {
                        continue;
                    }
                }
            }
            if (!empty($value)) { // 对数据过滤
                $filterList = [];
                foreach ($dealData as $key => $v) {
                    if (is_array($v) && is_numeric($key)) { // 这个说明是二维数组的那种list形式
                        extract($v);
                        $filterList[] = compact($value);
                    } else {
                        if (in_array($key, $value)) {
                            $filterList[$key] = $v;
                        }
                    }
                }
                $dealData = $filterList;
            }
        }
    }

    /**
     * @author yanshinian@yeah.net
     * @desc 统一格式化分页数据格式。全站保持一致。
     */
    public function pageFormatData()
    {
        if (empty($this->pageRule['key'])) {
            throw  new \Exception("no key");
        }
        $keys = explode('.', $this->pageRule['key']);
        $list = [];
        $page = [];
        if (isset($this->result[$keys[0]]) && is_array($this->result[$keys[0]]) && !empty($this->result[$keys[0]])) {
            $list = $this->result[$keys[0]];
            if (isset($this->pageRule['total'])) {
                $page['page_data_count'] = $this->result[$this->pageRule['total']];
            }
            if (isset($this->condition['pagesize'])) {
                $page['page_size'] = $this->condition['pagesize']; // 分页数目
                $page['page_total'] = ceil($page['page_data_count'] / $page['page_size']);
                if (isset($this->condition['pageindex'])) {
                    $page['page_current'] = (int) $this->condition['pageindex']; // 分页数目
                    $page['page_has'] = $page['page_total'] >= ($this->condition['pageindex'] + 1) ? true : false;
                }
            }
        }
        unset($this->result[$keys[0]]);
        if (isset($this->pageRule['total'])) {
            unset($this->result[$this->pageRule['total']]);
        }

        $this->result['list'] = $list;
        $this->result['page'] = $page;
    }
}
