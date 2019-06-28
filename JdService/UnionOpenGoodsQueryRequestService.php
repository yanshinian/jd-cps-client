<?php

/**
 * Created by PhpStorm.
 * User: yanshinian@yeah.net
 * Date: 2019/5/22
 * Time: 18:13
 *
 */

namespace JdService;

class UnionOpenGoodsQueryRequestService extends JdRequestService
{
    /**
     * @var string
     */
    public $requestName = 'UnionOpenGoodsQueryRequest';
    public $fillable = [
        'data' => ['brandCode'],
    ];
    public $pageRule = [
        'key' => 'data',
        'total' => 'totalCount',
    ];

    /**
     * @author yanshinian@yeah.net
     * @desc 公共查找接口
     *
     * @param array $where
     *
     * @return array|mixed|\ResultSet|\SimpleXMLElement
     */
    public static function getList(array $where = []):array
    {
        $defaultWhere = [
            'page_index' => 1,
            'page_size' => 2,
        ];
        $where = array_merge($defaultWhere, $where);
        $result = UnionOpenGoodsQueryRequestService::where($where)->Data();
        
        return $result;
    }
}
