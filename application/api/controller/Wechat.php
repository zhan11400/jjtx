<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/9/2 12:20
 */

namespace app\api\controller;


use app\api\model\Member;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\Text;
use think\Controller;
use think\Session;

class Wechat extends Controller
{
    protected static $app;
    function __construct()
    {
        Session::start();
        $options = [
            'debug'  => true,
            'app_id' => config('wx.app_id'),
            'secret' => config('wx.app_secret'),
            'token'  => config('wx.token'),

            'log' => [
                'level'      => 'debug',
                'permission' => 0777,
                'file'       => 'C:/temp/easywechat.log',
            ],

            // ...
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => 'http://www.gdjjtx.com/api/Wechat/oauthCallback',
            ],

            /**
             * 微信支付
             */
            /*
            'payment' => [
                'merchant_id'        => 'your-mch-id',
                'key'                => 'key-for-signature',
                'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
                'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
                // 'device_info'     => '013467007045764',
                // 'sub_app_id'      => '',
                // 'sub_merchant_id' => '',
                // ...
            ],*/
            /**
             * Guzzle 全局设置
             */
            'guzzle' => [
                'timeout' => 10.0, // 超时时间（秒）
                'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
            ],
            // ..
        ];
        // 使用配置来初始化一个项目。
        self::$app = new Application($options);
    }
    public function app()
    {
        return self::$app;
    }
    function run()
    {
        $server = self::$app->server;
        $this->responseMsg($server);
        $server->serve()->send();
    }
    function responseMsg($server)
    {
        $server->setMessageHandler(function ($message) {
            switch ($message->MsgType) {
                case 'event':
                    return $this->receiveEvent($message);
                    break;
                case 'text':
                    //获取到用户发送的文本内容
                    $content = $message->Content;
                    //把内容发给用户
                    return new Text(['content' => $content]);
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                // ... 其它消息
                default:
                    return '收到其它消息';
                    break;
            }
            // ...
        });
    }

    public function menu()
    {
        $menu = self::$app->menu;
         $buttons = [
            [
                "type" => "view",
                "name" => "集杰汇",
                "url"  => "http://www.gdjjtx.com/wxjjtx/#/"
            ],
            [
                "name" => "查询天下",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "波箱油查询",
                        "url"  => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "四虑三格查询",
                        "url"  => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "电池查询",
                        "url" => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "刹车皮查询",
                        "url" => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "火花塞查询",
                        "url" => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                ],

            ],
            [
                "name" => "资料总汇",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "下载星星车宝APP",
                        "url"  => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "培训教材",
                        "url"  => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "视频总汇",
                        "url" => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                    [
                        "type" => "view",
                        "name" => "人才库",
                        "url" => "http://www.zhan666.cn/home/lists/detail/cid/9/id/90.html"
                    ],
                ],

            ],
        ];
        return $menu->add($buttons);
    }

    public function access_token()
    {
        // 获取 access token 实例
        $accessToken = self::$app->access_token;
        $token = $accessToken->getToken(); // token 字符串
        return $token;
    }
    // 强制重新从微信服务器获取 token.
    public function forceRefresh()
    {
        // 获取 access token 实例
        $accessToken = self::$app->access_token;
        $token = $accessToken->getToken(true);
        return $token;
    }

    public function oauth()
    {
        $oauth = self::$app->oauth;
        return $oauth->redirect()->getTargetUrl();
    }
    public function oauthCallback()
    {
        $oauth = self::$app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        $wxResult = $user->toArray();

        $user = Member::getByOpenID($wxResult['original']['openid']);
        if (empty($user)){
            $uid = 0;
        }
        else {
            $uid = $user['id'];
        }
        $cachedValue = $this->prepareCachedValue($wxResult, $uid);
        $code = getRandChar(20);
        cache($code, json_encode($cachedValue['original']), config('secure.expire_in'));
        //cache($cachedValue['original']['openid'], json_encode($cachedValue['original']), config('secure.expire_in'));
        $targetUrl = 'http://www.gdjjtx.com/wxjjtx/#/login?';
        header('location:'. $targetUrl.'code='.$code);
    }

    private function prepareCachedValue($wxResult, $uid)
    {
        $cachedValue = $wxResult;
        $cachedValue['uid'] = $uid;
        return $cachedValue;
    }
    //接收用户事件，关注等
    private function receiveEvent($message)
    {
        switch ($message->Event)
        {
            case "subscribe":
                $contentStr = "欢迎关注我们";
                break;
            case "unsubscribe":
                $contentStr = "感谢你的使用，欢迎下次回来！";
                break;
            case "CLICK":
                break;
            case "LOCATION":
                $position['lat'] = $message->Latitude;
                $position['lng'] = $message->Longitude;
                session('position',$position);
                break;
            default:
                $contentStr = "";
                break;
        }
        return $contentStr;
    }

}