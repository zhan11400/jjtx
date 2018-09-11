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
			$data['role_id']=input('role_id/d');
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
		$list = db('admin_role')->order('role_id desc')->select();
		$this->assign("role",$list);
		return 	$this->fetch();
	}

	/*
	*	编辑管理员界面
	*/
	public function edit(){
		$id  = input("id");
		$res = $this->admin->where("id='$id'")->find();
		$list = db('admin_role')->order('role_id desc')->select();
		$this->assign("role",$list);
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
		if($id==1) $this->error('超级管理员不能删除');
		$res=$this->admin->where("id='$id'")->delete();
		if(!$res) $this->error('删除失败');
		$this->success("删除成功!",url('admin/li'));
	}
	/*
*	删除管理员
*/
	public function ban(){
		$id = input("id");
		if(!$id) $this->error('参数有误');
		$da=$this->admin->where("id='$id'")->find();
		if(!$da) $this->error('账号不存在');
		if($id==1) $this->error('超级管理员不能修改');
		if($da['staus']==1){
			$res=$this->admin->where("id='$id'")->update(["staus"=>0]);
		}else{
			$res=$this->admin->where("id='$id'")->update(["staus"=>1]);
		}
		$this->success("操作成功!",url('admin/li'));
	}
	//权限列表
	function right_list(){
		//权限组
		$group = array('system'=>'系统设置','finance'=>'财务管理','goods'=>'产品管理','member'=>'客户管理',
				'order'=>'订单管理','admin'=>'权限管理');
		$right_list = db('system_menu')->paginate(15);
		$this->assign('right_list',$right_list);
		$this->assign('group',$group);
		return $this->fetch();
	}
	public function test()
	{
		$condition['a.id'] =2;
		$admin_info = db('admin')->alias("a")->join('admin_role r', 'a.role_id=r.role_id', 'INNER')->where($condition)->find();
		var_dump($admin_info['act_list']);
	}
	//编辑权限
	public function edit_right(){
		if(request()->isPost()){
			$data = input();
			$data['right'] = implode(',',$data['right']);
			if(!empty($data['id'])){
				db('system_menu')->where(array('id'=>$data['id']))->update($data);
			}else{
				if(db('system_menu')->where(array('name'=>$data['name']))->count()>0){
					$this->error('该权限名称已添加，请检查',url('admin/right_list'));
				}
				unset($data['id']);
				db('system_menu')->insert($data);
			}
			$this->success('操作成功',url('admin/right_list'));
			exit;
		}
		$id = input('id');
		if($id){
			$info = db('system_menu')->where(array('id'=>$id))->find();
			$info['right'] = explode(',', $info['right']);
			$this->assign('data',$info);
		}
		//权限组
		$group = array('system'=>'系统设置','finance'=>'财务管理','goods'=>'产品管理','member'=>'客户管理',
				'order'=>'订单管理','admin'=>'权限管理');
		$planPath = APP_PATH.'Admin/Controller';
		$planList = array();
		$dirRes   = opendir($planPath);
		while($dir = readdir($dirRes))
		{
			if(!in_array($dir,array('.','..','.svn')))
			{
				$planList[] = basename($dir,'.php');
			}
		}
		$this->assign('planList',$planList);
		$this->assign('group',$group);
		return $this->fetch();
	}
	//删除权限
	public function right_del(){
		$id = input('del_id');
		if(is_array($id)){
			$id = implode(',', $id);
		}
		if(!empty($id)){
			$r = db('system_menu')->where("id in ($id)")->delete();
			if($r){
				$this->success('操作成功');
			}else{
				$this->error('删除失败');
			}
		}else{
			$this->error('参数有误');
		}
	}
	//控制器获取下面的function
	function ajax_get_action()
	{
		$control = input('controller');
		$advContrl = get_class_methods("app\\admin\\controller\\".str_replace('.php','',$control));
		$baseContrl = get_class_methods('app\admin\controller\Common');
		$diffArray  = array_diff($advContrl,$baseContrl);
		$html = '';
		foreach ($diffArray as $val){
			$html .= "<option value='".$val."'>".$val."</option>";
		}
		exit($html);
	}
//角色管理
	public function role(){
		$list = db('admin_role')->order('role_id desc')->select();
		$this->assign('list',$list);
		return $this->fetch();
	}
	//角色详情
	public function role_info(){
		$role_id =input('role_id/d');
		$detail = array();
		if($role_id){
			$detail = db('admin_role')->where("role_id",$role_id)->find();

			$detail['act_list'] = explode(',', $detail['act_list']);
			$this->assign('detail',$detail);
		}
		$right = db('system_menu')->order('id')->select();
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}
		//权限组
		$group = array('system'=>'系统设置','finance'=>'财务管理','goods'=>'产品管理','member'=>'客户管理',
				'order'=>'订单管理','admin'=>'权限管理');

		$this->assign('group',$group);
		$this->assign('modules',$modules);
		return $this->fetch();
	}
	//角色保存
	public function roleSave(){
		$data = input('post.');
		$res = $data['data'];
		if(!isset($data['right'])){
			$data['right']='';
		}
		$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
		if(empty($data['role_id'])){
			$r = db('admin_role')->insertGetId($res);
			$data['role_id']=$r;
		}else{
			$r =db('admin_role')->where('role_id', $data['role_id'])->update($res);
		}
		if($r){
			//adminLog('管理角色');
			$this->success("操作成功!",url('Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->success("操作失败!",url('Admin/role'));
		}
	}
	//删除角色
	public function roleDel(){
		$role_id = input('post.role_id/d');
		$admin = db('admin')->where('role_id',$role_id)->find();
		if($admin){
			exit(json_encode("请先清空所属该角色的管理员"));
		}else{
			$d = db('admin_role')->where("role_id", $role_id)->delete();
			if($d){
				exit(json_encode(1));
			}else{
				exit(json_encode("删除失败"));
			}
		}
	}
}