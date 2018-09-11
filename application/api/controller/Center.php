<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/26 15:29
 */
namespace app\api\controller;
use \app\api\logic\Center as CenterLogic;
use think\Controller;
use think\Request;
class Center extends Base
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
	//个人资料
	public function index(){
		return $this->ajaxReturn(
				$this->CenterLogic->center()
		);
	}
	//通知信息
	public function tip(){
		return $this->ajaxReturn(
				$this->CenterLogic->news_log()
		);
	}
	//信用额
	public function credit(){
		return $this->ajaxReturn(
				$this->CenterLogic->credit()
		);
	}
	//信用额记录
	public function credit_log(){
		return $this->ajaxReturn(
				$this->CenterLogic->credit_log()
		);
	}
	//绑定
	public function bind(){
		return $this->ajaxReturn(
				$this->CenterLogic->bind()
		);
	}
	//删除
	public function del(){
		return $this->ajaxReturn(
				$this->CenterLogic->delete()
		);
	}
	//已读
	public function read(){
		return $this->ajaxReturn(
				$this->CenterLogic->read()
		);
	}
}