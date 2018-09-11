<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */

namespace app\api\controller;
use app\api\service\Nearby;
use app\api\model\Member as MemberModel;
use app\api\validate\TestParameter;
use app\api\validate\Login as LoginValidate;
use think\Cache;

class Test extends Base
{
    private $appid='wx03d1e2e70938ac79';
    private $secret='808fb0442c9f9529c41a24800bfc1492';
    public function index()
    {
      /*  if(!Cache::get('access_token')){
            $accesstion=$this->getAccesstion();
            var_dump($accesstion);
            if(array_key_exists('access_token', $accesstion)){
                Cache::set("access_token",$accesstion['access_token'],$accesstion['expires_in']);
            }else{
                exit('错误代码：'.$accesstion['errcode'].',错误内容：'.$accesstion['errmsg']);
            }
        }*/

        $callback=__ROOT__.url("api/test/callback");
        $url=$this->getOauthCodeUrl($callback);
        $this->redirect($url);
        //$callback=__ROOT__.url("api/test/callback2");
        //$accesstion=$this->getOauthUserInfoUrl($callback);
    }

    /**
     * @return 获取微信access_token
     */
    public function getAccesstion()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $data=httpRequest($url);
        $json=json_decode($data,true);
        if(array_key_exists('access_token', $json)){
            Cache::set("access_token",$json['access_token'],$json['expires_in']);
		}
        return $json;
    }
    /**
     * @return 获取微信code
     */
    public function getOauthCodeUrl($callback, $state = '') {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
    }

    public function getOauthUserInfoUrl($callback, $state = '') {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
    }

    public function callback()
    {
        $code=input("code");
        if(!$code){
            exit('非法访问');
        }
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->secret}&code={$code}&grant_type=authorization_code";
        $data=httpRequest($url);
        file_put_contents("access_token.txt",\GuzzleHttp\json_encode($data));
        $targetUrl = 'http://www.gdjjtx.com/wxjjtx/#/login?';
        $this->redirect($targetUrl);
    }
    public function callback2()
    {
        file_put_contents("cb.txt",\GuzzleHttp\json_encode(input()));
    }
}