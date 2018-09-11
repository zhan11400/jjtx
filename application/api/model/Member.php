<?php
/**
 * Created by PhpStorm.
 * User: Tsang
 * Date: 2017/9/24 16:20
 *
 */

namespace app\api\model;


use think\Model;

class Member extends Model
{

    /**
     * 获取用户信息
     * @param $original
     * @return array
     */
    public function getUser($original)
    {
        $openid = $original['original']['openid'];
        $userData = $this->getByOpenID($openid);
        if (empty($userData)){
            $this->createUser($original['original']);
            $userData = $this->getByOpenID($openid);
        }else{
            $this->updateUser($userData['id'],$original['original']);
        }
        return $userData;
    }
    /**
     * 微信openid对应的用户是否存在
     * 存在返回uid，不存在返回0
     */
    public static function getByOpenID($openid)
    {
        $user = Member::where('wx_openid', '=', $openid)->find();
        if(empty($user)){
            return [];
        }
        $member = $user->toArray();
        unset($member['passwd']);
        return $member;
    }

    /**
     * 根据id获取用户信息
     * @param $uid
     * @return array
     *
     */
    public static function getByUID($uid)
    {
        $user = Member::where('id', '=', $uid)
            ->find();
        if(empty($user)){
            return [];
        }
        $member = $user->toArray();
        unset($member['passwd']);
        return $member;
    }
    /**
     * 更新用户
     * @param $root
     * @param $code
     * @return \think\response\Json
     */
    public function updateUser($uid,$original)
    {
        Member::update(
            [
                'wx_openid'    => $original['openid'],
                "province"=> $original['province'],
                "city"=> $original['city'],
                'nickname' => $original['nickname'],
                'sex' => $original['sex'],
                'headimage' => $original['headimgurl'],
            ],
            ['id' => $uid]
        );
    }

    /**
     * 创建用户
     * @param $original
     * @return mixed
     */
    private function createUser($original)
    {
        $user = MemberModel::create(
            [
                'wx_openid'    => $original['openid'],
                "province"=> $original['province'],
                "city"=> $original['city'],
                'nickname' => $original['nickname'],
                'sex' => $original['sex'],
                'headimgurl' => $original['headimgurl'],
                'register_time' => time(),
            ]);
        if ($user->id == 0)
            return array('message'=>'授权登录失败');
    }

}