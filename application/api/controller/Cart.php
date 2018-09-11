<?php
/**
 * Created by PhpStorm.
 * member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\controller;
use think\Request;
use \app\api\logic\Cart as CartLogic;
use think\Db;
class Cart extends Base
{
    public $request;
    public $Cart; // 首页逻辑操作类

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->Cart = new CartLogic();//逻辑业务
        $this->request = $request;
    }
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => [  'except' => ''],
    ];

//添加购物车
    public function cart_add()
    {
        $data = $this->Cart->cart_add();
        return $this->ajaxReturn($data);
    }

//勾选购物车中的商品
    public function select()
    {
        $data = $this->Cart->cart_select();
        return $this->ajaxReturn($data);
    }

//更新购物车的数量
    public function update()
    {
        $data = $this->Cart->cart_update();
        return $this->ajaxReturn($data);
    }
//购物车列表
    public function index()
    {
        $data = $this->Cart->cart_list();
        return $this->ajaxReturn($data);
    }

//移除购物车
    public function remove()
    {
        $data = $this->Cart->cart_remove();
        return $this->ajaxReturn($data);
    }
}