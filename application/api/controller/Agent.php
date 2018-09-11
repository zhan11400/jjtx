<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/26 15:29
 */
namespace app\api\controller;
use \app\api\logic\Agent as CenterLogic;
use think\Controller;
use think\Request;
class Agent extends Base
{
	//检查权限作用域
	protected $beforeActionList  = [
			'checkPrimaryScope' => [
					'except' => ''
			],
	];
	public function __construct(Request $request){
		parent::__construct($request);
		$this->request = $request;
		$this->CenterLogic = new CenterLogic();//逻辑业务
	}
	/**
	 * 代理商中心
	 */
	public function index(){
		return $this->ajaxReturn(
				$this->CenterLogic->center()
		);
	}
	/**
	 * 门店
	 */
	public function shop(){
		return $this->ajaxReturn(
				$this->CenterLogic->shop()
		);
	}
	/**
	 * 订单
	 * */
	public function order(){
		return $this->ajaxReturn(
				$this->CenterLogic->order_list()
		);
	}

	//审核
	public function deal(){
		return $this->ajaxReturn(
				$this->CenterLogic->deal()
		);
	}
	public function detail(){
		return $this->ajaxReturn(
				$this->CenterLogic->order_detail()
		);
	}

}