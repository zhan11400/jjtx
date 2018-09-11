<?php
namespace app\index\Controller;
use think\Controller;
use think\Db;
class Common extends Controller {
  
	//保存管理员数据的变量
	public $agent = array();
	//统计网站管理员
	private $admin_number = 0;
	//表变量
	private $table_admin ;
	function __construct(){
		parent::__construct();
		$this->table_admin = db("admin");
		$this->checkLogin();
        define('ACTION_NAME',$this->request->action()); // 当前操作名称是
	}
    //判断是否已经登陆
	private function checkLogin()
	{
		$this->agent= session("agent");
		if(!$this->agent){
			$this->redirect("login/login");
		}else{
			$this->assign("info",$this->agent);
		}
	}

  
}