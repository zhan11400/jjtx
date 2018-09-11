<?php
namespace app\admin\Controller;
use think\Controller;
use think\Db;
class Common extends Controller {
  
	//保存管理员数据的变量
	private $info = array();
	//统计网站管理员
	private $admin_number = 0;
	//表变量
	private $table_admin ;
	function __construct(){
		parent::__construct();
		$this->table_admin = db("admin");
		define('MODULE_NAME',$this->request->module());  // 当前模块名称是
		define('CONTROLLER_NAME',$this->request->controller()); // 当前控制器名称
		define('ACTION_NAME',$this->request->action()); // 当前操作名称是
		$this->checkLogin();
		$this->check_priv();
	}
    //判断是否已经登陆
	private function checkLogin()
	{
		$this->info = session("info");
		if(!$this->info){
			$this->redirect("login/login");
		}else{
			$this->assign("info",$this->info);

			$this->admin_number = $this->table_admin->count();
			$this->assign("admin_number",$this->admin_number);
		}
	}
	//检测权限
	public function check_priv()
	{

		$ctl = CONTROLLER_NAME;
		$act = ACTION_NAME;
		$act_list = session('act_list');
		//$act_list="64,65,66";
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
		$this->assign("active",$ctl.'/'.$act);
		$this->assign("power",$ctl.'@'.$act);
		if($act_list == 'all'){
			$this->assign("role_right",'all');
			$this->assign("group_power",'all');
			return true;
		}elseif(strpos($act,'ajax') || in_array($act,$uneed_check)){
			//所有ajax请求不需要验证权限
			return true;
		}else{
			$group = db('system_menu')->where("id", "in", $act_list)->column('group');
			$group_power='';
			foreach ($group as $v){
				$group_power .= $v.',';
			}
			$right = db('system_menu')->where("id", "in", $act_list)->column('right');
			$role_right='';
			foreach ($right as $val){
				$role_right .= $val.',';
			}
			$role_right = explode(',', $role_right);
			$group_power = explode(',', $group_power);
			//检查是否拥有此操作权限
			if(!in_array($ctl.'@'.$act, $role_right) && $ctl!='Index'){
				$this->error('您没有操作权限,请联系超级管理员分配权限',url('Index/index'));
			}
			$this->assign("role_right",$role_right);
			$this->assign("group_power",$group_power);
		}
	}
  
}