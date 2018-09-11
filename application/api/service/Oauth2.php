<?php
/**
 * token service
 * Member: chan
 * Date: 2017/8/4 Time: 19:05
 */

namespace app\api\service;
use app\lib\exception\TokenException;
use think\Cache;
use think\Exception;


class Oauth2
{
    private static $Token;
    public static $expire_in;
    public static $refresh_token;
    public function __construct()
    {
        self::$Token = db("token");
        self::$expire_in = config('token.expire_in');
        self::$refresh_token = rand();
    }

    /**
     * 生成令牌
     * @param $user
     * @return array
     */
    public static function generateToken($user)
    {
        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME'];
        $tokenSalt = config('token.token_salt');
        $token = md5($randChar . $timestamp . $tokenSalt);//加上账号
        return self::saveToCache($token,$user,md5($randChar),$timestamp);
    }

    /**
     * 获取token
     * @param $access_token
     * @return array
     * @throws TokenException
     */
    private function getCurrentTokenVar($access_token)
    {
        $token = Cache::get($access_token);
        if (!$token){
            throw new TokenException([
                'msg'=>'访问令牌不存在或已失效',
                'errorCode'=>10002
            ]);
        }else {
            $tokenArr = object_to_array(json_decode($token));
            //self::validateTime($tokenArr);
            return object_to_array(json_decode($token));
        }
    }
    public function getUserInfoByToken($access_token)
    {
        return $this->getCurrentTokenVar($access_token);
    }
    /**
     * 写入缓存
     * @param $token
     * @param $user
     * @param $randChar
     * @param $timestamp
     * @return array
     * @throws TokenException
     */
    private static function saveToCache($token,$user,$randChar,$timestamp)
    {
        $values = [
            'user'=>$user,
            'access_token'=>$token,
            'expire_in'=>self::$expire_in,
            'refresh_token'=>$randChar,
            'timestamp'=>$timestamp,
            'scope' => ''
        ];
        try{
            cache($randChar, json_encode($values), self::$expire_in*24*30);
            cache($token, json_encode($values), self::$expire_in);
        }catch (Exception $e){
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $values;
    }

    /**
     * 更新令牌
     *
     * 如果用户访问的时候，客户端的"访问令牌"已经过期，则需要使用refresh_token申请一个新的access_token访问令牌。
     * 如果请求的时候提示refresh_token也已经过期,说明这个用户至少很久没有活跃了，那么这时候用户的登录状态就会过期
     * refresh_token只用一次，每次刷新token的时候都会返回一个新的refresh_token
     * @param $refresh_token
     * @param $access_token
     * @return array
     * @throws TokenException
     */
    public function refreshToken($refresh_token,$access_token)
    {
        $token = $this->getCurrentTokenVar($access_token);
        if ($token['access_token'] != $access_token){
            throw new TokenException([
                'msg' => '更新令牌不存在或已过期',
                'errorCode' => 10001
            ]);
         }
        Cache::rm($refresh_token);
        Cache::rm($access_token);
        $user = $this->generateToken($token['user']);
        //unset($user['user']);
        return $user;
    }

    /**
     * 验证token
     * @param $clientToken
     * @param $serverToken
     * @throws TokenException
     */
    public function verifyToken($clientToken,$serverToken)
    {
        if ($clientToken != $serverToken) {
            throw new TokenException([
                'msg'=>'令牌不合法',
                'errorCode'=>10002
            ]);
        }
    }

    /**
     * @param $tokenArr
     * @throws TokenException
     */
    public static function validateTime($tokenArr)
    {
        $currentTimestamp = $_SERVER['REQUEST_TIME'];
        if ($tokenArr['timestamp'] + $tokenArr['expire_in'] - $currentTimestamp < 0) {
            throw new TokenException([
                'msg'=>'访问令牌过期',
                'errorCode'=>10002
            ]);
        }
    }
}