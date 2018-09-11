<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/26 15:29
 */

namespace app\api\controller;

use think\Request;

class Login extends Common
{
    public function __construct(Request $request)
    {
        parent::__construct($request);

    }
    //检查权限作用域
    protected $beforeActionList  = [
        'authorization' => ['only' => 'login'],
    ];
    public function login()
    {
       $login =  (new \app\api\logic\Login())->login();
       return $this->ajaxReturn($login);
    }

    //注册
    public function register()
    {
      $register =  (new \app\api\logic\Login())->register();
      return $this->ajaxReturn($register);
    }
    //忘记密码
    public function forget()
    {
       $forget =  (new \app\api\logic\Login())->forget();
       return $this->ajaxReturn($forget);
    }
}