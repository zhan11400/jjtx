<?php
/**
 * wecaht login class .
 * User: chan
 * Date: 2017/9/12  17:12
 */
namespace app\api\logic;

use app\api\BaseModel;
use app\api\model\Member as MemberModel;

class Member extends BaseModel
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
     * 更新用户
     * @param $root
     * @param $code
     * @return \think\response\Json
     */
    public function updateUser($uid,$original)
    {
        MemberModel::update(
            [
                'wx_openid' => $original['openid'],
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
                'wx_openid' => $original['openid'],
                "province"=> $original['province'],
                "city"=> $original['city'],
                'nickname' => $original['nickname'],
                'sex' => $original['sex'],
                'headimage' => $original['headimgurl'],
            ]);
        if ($user->id == 0)
            return array('message'=>'授权登录失败');
    }
}