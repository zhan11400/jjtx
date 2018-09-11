<?php
namespace app\api\controller;
ini_set("display_errors", "on");
vendor('api_sdk.vendor.autoload');

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use think\Cache;

// 加载区域结点配置
Config::load();

class Msn
{
    /**
     * 构造器
     *
     * @param string $accessKeyId 必填，AccessKeyId
     * @param string $accessKeySecret 必填，AccessKeySecret
     */
    public function __construct()
    {
        $accessKeyId=config('aliyun.accessKeyId');
        $accessKeySecret=config('aliyun.accessKeySecret');
        // 短信API产品名
        $product = "Dysmsapi";

        // 短信API产品域名
        $domain = "dysmsapi.aliyuncs.com";

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";

        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        // 增加服务结点
        DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

        // 初始化AcsClient用于发起请求
        $this->acsClient = new DefaultAcsClient($profile);

    }
    public function sendSms($signName, $templateCode, $phoneNumbers, $templateParam = null, $outId = null) {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($phoneNumbers);

        // 必填，设置签名名称
        $request->setSignName($signName);

        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);

        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }

        // 可选，设置流水号
        if($outId) {
            $request->setOutId($outId);
        }

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);

        // 打印请求结果
        // var_dump($acsResponse);

        return $acsResponse;

    }

    /*
     * 短信通知
     */
    public function sms($mobile,$type)
    {
        header('Content-Type: text/plain; charset=utf-8');
        switch ($type) {
            case 'agree':
                $SMS = 'SMS_106515026';
                break;
            case 'refuse':
                $SMS = 'SMS_106325011';
                break;
        }
        $response = $this->sendSms(
            "汇提网", // 短信签名
            $SMS, // 短信模板编号
            $mobile, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>1,
                "product"=>"E斗星"
            ),
            "123"
        );
        if($response->{'Code'}=='OK'){
            return  array();
        }else{
            return   array("message"=>'系统繁忙');//"{\"data\":[],\"code\":-1,\"message\":\"系统繁忙\"}";
        }
    }
    /*
     * 验证码
     */
    public function index()
    {
        header('Content-Type: text/plain; charset=utf-8');
        $mobile=input('mobile');
        if(!$mobile || strlen($mobile)!=11){
            echo   "{\"data\":[],\"code\":-1,\"message\":\"手机号码有误\"}";exit;
        }
        $code=rand(1000,9999);
        Cache::set('code',base64_encode($code),3600);
        $type=input('type');
        Cache::set("mobile",$mobile,3600);
        $where['mobile'] = $mobile;
        $where['status'] = 1;
        $member = db("member")->where($where)->find();
        switch ($type)
        {
            case 'register':
                Cache::set("code",base64_encode($code),3600);
                 $SMS='SMS_113915101';//用户注册验证码
                break;
            case 'forget':
                if(!$member) {
                    echo   "{\"data\":[],\"code\":-1,\"message\":\"手机没注册\"}";exit;
                }
                Cache::set("forget",base64_encode($code),3600);
                 $SMS='SMS_113915099';//信息变更验证码
                break;
            case 'relieve':
                Cache::set("relieve",base64_encode($code),3600);
                $SMS='SMS_113915105';//身份验证验证码
                break;
            case 'bind':
                if($member) {
                    echo   "{\"data\":[],\"code\":-1,\"message\":\"该手机号码已绑定其他账户\"}";exit;
                }
                Cache::set("bind",base64_encode($code),3600);
                 $SMS='SMS_113915099';//信息变更验证码
                break;
            case 'destroy'://销毁账号
                if(!$member) {
                    echo   "{\"data\":[],\"code\":-1,\"message\":\"该手机没注册\"}";exit;
                }
                Cache::set("destroy",base64_encode($code),3600);
                $SMS='SMS_102435092';
                break;
            default:
                Cache::set("code",base64_encode($code),3600);
                $SMS='SMS_113915101';//用户注册验证码
        }
        $response = $this->sendSms(
            "阿里云短信测试专用", // 短信签名
            $SMS, // 短信模板编号
            $mobile, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>$code,
                "product"=>"E斗星"
            ),
            "123"
        );
       // var_dump($response);
        if($response->{'Code'}=='OK'){
            echo   "{\"data\":[],\"code\":1}";exit;
        }else{
            echo   "{\"data\":[],\"code\":-1,\"message\":\"系统繁忙\"}";exit;
        }

    }
}


