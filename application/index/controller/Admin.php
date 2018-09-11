<?php
namespace app\admin\Controller;
class Admin extends Common {
	
	
	//管理员表
	private $table_admin = "";
	
	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();
		$this->admin =db("admin");
	}
	
	/*
	*	添加管理员界面
	*/
    public function add(){

		if(request()->isPost()){
			$account   = input("account");
			$passwd    = input("passwd");
			if(!$account || !$passwd) $this->error('账号或密码为空');
			$data['account']   = $account;
			$data['password']    = md5($passwd);
			$data['real_name'] =input("real_name");
			$data['phone']     = input("phone");
			$data['address']   = input("address");
			$data['addtime']   = time();
			$id=input('id');
			if($id){
				$this->admin->where("id='$id'")->update($data);
			}else {
				$da=$this->admin->where("account='$account'")->find();
				if($da) $this->error('账号已存在');
				$this->admin->insert($data);
			}
			$this->success("操作成功!",url('admin/li'));
		}
	return 	$this->fetch();
    }

	/*
	*	编辑管理员界面
	*/
    public function edit(){
		$id  = input("id");
		$res = $this->admin->where("id='$id'")->find();
		$this->assign("res",$res);
			return 	$this->fetch();
    }
	/*
	*	管理员列表
	*/
    public function li(){
		$list = $this->admin ->paginate(10);
		$this->assign('data',$list);
        return $this->fetch();
    }
	
	/*
	*	删除管理员
	*/
	public function del(){
		$id = input("id");
		if(!$id) $this->error('参数有误');
		$da=$this->admin->where("id='$id'")->find();
		if(!$da) $this->error('账号不存在');
		$res=$this->admin->where("id='$id'")->delete();
		if(!$res) $this->error('删除失败');
		$this->success("删除成功!",url('admin/li'));
	}
	
	
}