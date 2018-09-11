<?php
/**
 * 商户
 * User: chen guang
 * Date: 2017/9/29 9:00
 *
 */

namespace app\index\Controller;


use think\Db;

class Set extends Common{

	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();

	}


	/**
	 * 设置
	 */
	public function index(){
		$agent=db("agent")->where("id=".$this->agent['id'])	->find();
		$this->assign('list',$agent);
		return $this->fetch();
	}
	/**
	 * 设置
	 */
	public function deal(){

	 $data['percentage']=floatval(input('value'));
	echo db("agent")->where("id=".$this->agent['id'])	->update($data);
		;exit;
	}
}