<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */

namespace app\api\controller;
class Index extends Base
{
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => [
            'only' => ''
        ],
    ];


    /**
     * 轮播图
     */
    public function banner()
    {
        $result =  (new \app\api\logic\Index())->banner();
        return $this->ajaxReturn($result);
    }
    /**
     * 分类
     */
    public function category()
    {
        $result =  (new \app\api\logic\Index())->category();
        return $this->ajaxReturn($result);
    }
    /**
     * 精品推荐
     */
    public function recommend()
    {
        $result =  (new \app\api\logic\Index())->recommend();
        return $this->ajaxReturn($result);
    }
    /**
     * 热门品牌
     */
    public function brand()
    {
        $result =  (new \app\api\logic\Index())->brand();
        return $this->ajaxReturn($result);
    }
    /**
     * 今日爆款
     */
    public function home()
    {
        $result =  (new \app\api\logic\Index())->home();
        return $this->ajaxReturn($result);
    }
    /**
     * 注册协议
     */
    public function contract()
    {
        $result =  (new \app\api\logic\Index())->contract();
        return $this->ajaxReturn($result);
    }
    /**
     * 经销商
     */
    public function agent()
    {
        $result =  (new \app\api\logic\Index())->agent();
        return $this->ajaxReturn($result);
    }

    /**
     * 联系我们
     */
    public function contact()
    {
        $result =  (new \app\api\logic\Index())->contact();
        return $this->ajaxReturn($result);
    }
    /**
     * 获取代理商
     */
    public function getagent()
    {
        $result =  (new \app\api\logic\Index())->getagent();
        return $this->ajaxReturn($result);
    }
    public function index()
    {
        $result =  (new \app\api\logic\Index())->index();
        return $this->ajaxReturn($result);
    }
}