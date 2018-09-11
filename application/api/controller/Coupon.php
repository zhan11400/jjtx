<?php
/**
 * Created by PhpStorm.
 * member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\controller;
use think\Request;
use \app\api\logic\Coupon as CollectLogic;
use think\Db;
class Coupon extends Base
{
    public $request;
    public $Collect; // 首页逻辑操作类

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->CollectLogic = new CollectLogic();//逻辑业务
        $this->request = $request;
    }
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => ['except' => ''],
    ];
    /**
     * 优惠券列表
     */
    public function index()
    {
        $data=$this->CollectLogic->index();
        return $this->ajaxReturn($data);
    }
    /**
     * 领取优惠券
     */
    public function receive()
    {
        $data=$this->CollectLogic->receive();
        return $this->ajaxReturn($data);
    }
    /**
     * 我的优惠券
     */
    public function mycoupon()
    {
        $data=$this->CollectLogic->mycoupon();
        return $this->ajaxReturn($data);
    }

    //商品收藏的列表
    public function collect_goods_list()
    {
        $data=$this->CollectLogic->collect_goods_list();
        return $this->ajaxReturn($data);
    }
    /*
     * 删除收藏的商品
     */
    public function collect_goods_remove()
    {
        $data=$this->CollectLogic->collect_goods_remove();
        return $this->ajaxReturn($data);
    }

    /*
     * 商品收藏与取消
     */
    public function collect_goods()
    {
        $data=$this->CollectLogic->collect_goods();
        return $this->ajaxReturn($data);
    }
}