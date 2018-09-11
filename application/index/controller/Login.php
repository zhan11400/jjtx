<?php
namespace app\index\Controller;
use think\captcha\Captcha;
use think\Controller;

class Login extends Controller
{
	private $table_admin = '';
	
	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();
		$this->table_admin = db("agent");
	}
    /*
	*	管理员登陆页面
	*/
	public function login()
    {
	   return $this->fetch();
	}

    public function captchaImg()
    {	$config =    [
			'codeSet'	  =>'1234567890',
		// 验证码字体大小
			'fontSize'    =>    30,
		// 验证码位数
			'length'      =>    4,
		// 关闭验证码杂点
			'useNoise'    =>    true,
	];
		$captcha = new Captcha($config);
		return $captcha->entry();
	}
	/*
	*	管理员登陆处理
	*/
	public function login_do(){
	   $account = input("post.account");
	   $passwd  = input("post.passwd");
	   if($account==""){
		   $this->error("账号不能为空");
	   }
	   if($passwd==""){
		   $this->error("密码不能为空");
	   }
	   $data = $this->table_admin ->where("mobile='$account'")->find();
       $verifyCode = input("verifyCode");
       $res = $this->check_verify($verifyCode);
       if($res!=true)  $this->error("验证码不正确！");
	   if(!$data){
		   $this->error("账号或者密码错误！");
	   }elseif($data['status']==0){
		   $this->error("该账号被禁止使用!");
	   }else{
		   if($data['password']!=md5($passwd)){
			   $this->error("账号或者密码错误！");
		   }else{
			   session("agent",$data);
			   $this->redirect('Index/Index');
		   }
	   }

	}
	
	/*
	*	管理员登出处理
	*/
	public function login_out(){
		session("info",NULL);
		$this->success("欢迎再次使用!",'login/login');
	}

    /**
     * 检测输入的验证码是否正确
     *
     * @param $code 为用户输入的验证码字符串，
     * @param $id 多个验证码标识
     *
     */
    function check_verify($code, $id = ''){
        $captcha = new Captcha();
        return $captcha->check($code, $id);
    }
   
   
}