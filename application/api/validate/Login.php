<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */
namespace app\api\validate;
class Login extends BaseValidate
{
    public function checkLoginParam()
    {
         $this->rule = [
            'mobile' => 'require|isMobile',
            'password' => 'require|isNotEmpty',
            'code' => 'require|isNotEmpty',
        ];
         $this->message = [
            'mobile.require' => '手机号码必填',
            'mobile.isMobile' => '手机号码格式错误',
            'password' => '密码必填',
            'code' => '微信授权失败',
        ];
    }
    public function checkRegisterParam()
    {
        $this->rule = [
            'mobile' => 'require|isMobile',
            'password' => 'require|isNotEmpty',
            'code' => 'require|isNotEmpty',
            'nickname' => 'require|isNotEmpty',
            'email' => 'require|isNotEmpty',
            'company' => 'require|isNotEmpty',
            'province' => 'require|isNotEmpty',
            'city' => 'require|isNotEmpty',
            'county' => 'require|isNotEmpty',
        ];
        $this->message = [
            'mobile.require' => '手机号码必填',
            'mobile.isMobile' => '手机号码格式错误',
            'password' => '密码必填',
            'code' => '验证码必填',
            'nickname' => '昵称必填',
            'email.require' => '邮箱必填',
            'email.email' => '邮箱格式错误',
            'company' => '公司必填',
            'province' => '地址省必填',
            'city' => '地址市必填',
            'county' => '地址区必填',
        ];
    }

    public function checkForgetParam()
    {
        $this->rule = [
            'mobile' => 'require|isMobile',
            'password' => 'require|isNotEmpty',
            'code' => 'require|isNotEmpty'
        ];
        $this->message = [
            'mobile.require' => '手机号码必填',
            'mobile.isMobile' => '手机号码格式错误',
            'password' => '密码必填',
            'code' => '验证码必填'
        ];
    }
}