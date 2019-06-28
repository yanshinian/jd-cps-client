<?php

/**
 * Created by PhpStorm.
 * User: yanshinian@yeah.net
 * Date: 2019/5/22
 * Time: 17:51.
 * jd.union.open.goods.query
 */
class UnionOpenGoodsQueryRequest
{
    /**
     * @var long
     */
    private $cid1;
    /**
     * @var long
     */
    private $cid2;
    /**
     * @var long
     */
    private $cid3;
    /**
     * @var int
     */
    private $pageIndex;
    /**
     * @var int
     */
    private $pageSize;
    /**
     * @var array
     */
    private $skuIds;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var float
     */
    private $pricefrom;
    /**
     * @var float
     */
    private $priceto;
    /**
     * @var int
     */
    private $commissionShareStart;
    /**
     * @var int
     */
    private $commissionShareEnd;
    /**
     * @var string
     */
    private $owner;
    private $apiParas = [];

    public function setCid1($cid1)
    {
        $this->cid1 = $cid1;
        $this->apiParas['cid1'] = $cid1;
    }

    public function setCid2($cid2)
    {
        $this->cid2 = $cid2;
        $this->apiParas['cid2'] = $cid2;
    }

    public function setCid3($cid3)
    {
        $this->cid2 = $cid3;
        $this->apiParas['cid3'] = $cid3;
    }

    public function setPageIndex($pageIndex)
    {
        $this->pageIndex = $pageIndex;
        $this->apiParas['pageIndex'] = $pageIndex;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        $this->apiParas['pageSize'] = $pageSize;
    }

    public function setSkuIds($skuIds)
    {
        $this->pageIndex = $skuIds;
        $this->apiParas['skuIds'] = $skuIds;
    }

    public function getApiMethodName()
    {
        return 'jd.union.open.goods.query';
    }

    public function getApiParas()
    {
        return ['goodsReqDTO' => $this->apiParas];
    }

    public function check()
    {
    }

    public function putOtherTextParam($key, $value)
    {
        $this->apiParas[$key] = $value;
        $this->$key = $value;
    }
}
