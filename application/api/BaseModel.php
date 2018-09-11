<?php
namespace app\api;
use think\Cache;
use think\Model;
use think\Request;

class BaseModel extends Model
{
    public $request;
    public $requestData;
    protected $user;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->request = Request::instance();
        $this->requestData = empty($data)?$this->request->param(''):$data;
        if ($this->request->__get('user')){
            $this->user = $this->request->__get('user');
        }
    }


    /**
     * 检查用户身份
     * @return int
     */
    public function checkUserStatus()
    {
        $userStatus = 0;//游客
        $percent=1;
        $uid=0;
        if(isset($this->requestData['access_token']) && !empty($this->requestData['access_token'])) {
            $Oauth2 = new \app\api\service\Oauth2();
            $Oauth = ($Oauth2->getUserInfoByToken($this->requestData['access_token']));
            if (isset($Oauth['user']) && !empty($Oauth['user'])){
                $member = checkMember($Oauth['user']['id']);
                $uid=$member['id'];
                $userStatus=$member['type'];
                $percent = $member['discount'];
            }
        }
        return array("type"=>$userStatus,'percent'=>$percent,"uid"=>$uid);
    }

    /**
     * 检测是不是经销商
     * @return int
     */
    public function checkagent($uid)
    {
        $agent=db("agent")->where("uid='$uid' and status='1'" )->find();
        if($agent){
            $ype=1;
        }else{
            $ype=0;
        }
        return $ype;
    }
    /**
     * 检查数据是否为空
     */
    public function checkData($data,$error_msg)
    {
        if(empty($data) || empty($error_msg)) return array('message' => '参数错误');
        foreach($data as $k => $v){
            if(!array_key_exists($v,$this->requestData)) return array('message' => $error_msg[$k]);
        }
        return array();
    }

    public function getmao($id)
    {
        $goods=db("goods")->where("id=".$id)->find();
        if($goods['mao']==1){
            $value=db("config")->where("id=5")->value('value');
        }
        if($goods['mao']==0){
            $value=db("config")->where("id=4")->value('value');
        }
        return floatval($value);
    }
    public function getCostprice()
    {
        $isagent=$this->checkagent($this->user['id']);
        $percent=0;
        if(!$isagent) {
            $member = checkMember($this->user['id']);
            if ($member['agent'] > 0) {
                $where['id'] = $member['agent'];
                $agent = db("agent")->where($where)->find();
                if ($agent) {
                    $agentmember = checkMember($agent['uid']);
                    if($agentmember) {
                        $percent = $agentmember['discount'];
                    }
                }
            }
        }
        return floatval($percent);
    }
}