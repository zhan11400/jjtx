<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/8/22 21:04
 */

namespace app\api\controller;
use \app\api\service\Oauth2 as TokenService;
use think\Exception;

class Oauth2 extends Base
{
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => ['only' => 'getUserInfoByToken'],
    ];
    public function refreshToken()
    {
        $refresh_token = input('post.refresh_token');
        $access_token = input('post.access_token');
        try{
            $refreshToken = (new TokenService())->refreshToken($refresh_token,$access_token);
            return $this->ajaxReturn($refreshToken);
        }catch (Exception $e){
            $returnData = [
                'data' =>array(),
                'code' => -1,
                'message' => $e->getMessage(),
                'errorCode' => $e->errorCode
            ];
            return json($returnData);
        }
    }


}