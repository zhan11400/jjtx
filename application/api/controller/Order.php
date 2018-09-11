<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/8/7 19:20
 */

namespace app\api\controller;
use think\Db;
use think\Request;
use \app\api\logic\Order as OrderLogic;
class Order extends Base
{
    public $request;
    public $OrderLogic; // 首页逻辑操作类
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->OrderLogic = new OrderLogic();//逻辑业务
        $this->request = $request;
    }

    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => ['except' => ''],
    ];
    //订单列表
    public function order_list()
    {
        $data = $this->OrderLogic->order_list();
         return $this->ajaxReturn($data);
    }
    //订单详情
    public function order_detail()
    {
        $data = $this->OrderLogic->order_detail();
        return $this->ajaxReturn($data);
    }
    /**
     * 提醒发货
     */
    public function remind(){
        $data = $this->OrderLogic->remind();
        return $this->ajaxReturn($data);
    }
    /**
     * 取消订单
     */
    public function order_cancel(){
        $data = $this->OrderLogic->order_cancel();
        return $this->ajaxReturn($data);
    }
    /**
     * 物流信息
     */
    public function logistics()
    {
        $data = $this->OrderLogic->express();
        return $this->ajaxReturn($data);
    }
    //确定订单
    public function order_confirm()
    {

        $data = $this->OrderLogic->order_confirm();
        return $this->ajaxReturn($data);
    }
    //提交订单
    public function order_create()
    {
        $data = $this->OrderLogic->order_create();
        return $this->ajaxReturn($data);
    }




    /**
     * 确认收货
     */
    public function order_receive(){

        $data = $this->OrderLogic->order_receive();
        return $this->ajaxReturn($data);
    }

    /**
     * 删除订单
     */
    public function deleted(){
        $data = $this->OrderLogic->order_deleted();
        return $this->ajaxReturn($data);
    }
    /**
     * 订单退款
     */
    public function order_refund(){
        $data = $this->OrderLogic->order_refund();
        return $this->ajaxReturn($data);
    }
    /**
     *退款详情
     */
    public function order_refund_detail(){
        $data = $this->OrderLogic->order_refund_detail();
        return $this->ajaxReturn($data);
    }

    /**
     * 评价商品信息
     */
    public function evaluate_info(){
        return $this->returnRequest($this->OrderLogic->evaluate_info());
    }


    /**
     * 订单评价
     */
    public function order_evaluate(){
        return $this->returnRequest($this->OrderLogic->order_evaluate());
    }


    /**
     * 获取快递列表
     */
    public function expressList(){
        return $this->returnRequest($this->OrderLogic->expressList());
    }


    /**
     * 获取退货地址
     */
    public function refundAddress(){
        return $this->returnRequest($this->OrderLogic->refundAddress());
    }

    /**
     * 退货信息
     */
    public function refundInfo(){
        return $this->returnRequest($this->OrderLogic->refundInfo());
    }


    public function orderList()
    {
        //加密数据包
        return json($this->requestData) ;
    }
//申请中
    public function refunding()
    {
        return $this->returnRequest($this->OrderLogic->refunding());
    }


}