<?php
/**
 * 收货地址
 * Member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\controller;
use think\Request;
use \app\api\logic\Address as AddressLogic;
use think\Db;

class Address extends Base
{
    public $AddressLogic; // 首页逻辑操作类

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->AddressLogic = new AddressLogic($this->requestData);//逻辑业务
    }
    
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => ['except' => ''],
    ];

    
    //添加修改收货地址
    public function edit(){
        return $this->ajaxReturn(
            $this->AddressLogic->edit()
        );
    }
    
    //设置默认收货地址
    public function setdefault(){
        return $this->ajaxReturn(
            $this->AddressLogic->setdefault()
        );
    }
    
    //删除收货地址
    public function del(){
        return $this->ajaxReturn(
            $this->AddressLogic->del()
        );
    }

    //收货地址详情
    public function detail(){
        return $this->ajaxReturn(
            $this->AddressLogic->detail()
        );
    }
    
    //收货地址列表
    public function address_list(){
        return $this->ajaxReturn(
            $this->AddressLogic->address_list()
        );
    }


}