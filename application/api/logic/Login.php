<?php
namespace app\api\logic;
use think\Cache;
use think\Request;
use app\api\validate\Login as LoginValidate;
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/26 15:30
 */
class Login extends \app\api\BaseModel
{
    /**
     * 用户登陆
     * @return array
     */
    public function login()
    {
        (new LoginValidate())->checkLoginParam();

        $tel = $this->requestData['mobile'];
        $passwd = $this->requestData['password'];

        $where['mobile'] = $tel;
     //   $where['status'] = 1;
        $root = db('member')->where($where)->find();
        if(!$root) return array('message'=>'账号不存在');
        if($root['status'] == 0) return array('message'=>'账号审核中');
        if($root['passwd'] != md5($passwd)) return array('message' => '密码错误');

        $Oauth2 =  new \app\api\service\Oauth2();
        $user = [
            'id'=> $root['id'],
            'userId'=> $root['id'],
            'uid'=> $root['id'],
            'mobile'=> $root['mobile'],
            'nickname'  => $root['nickname'],
            'headimage'=> config('url.uploads') .$root['headimage'],
            'sex' => $root['sex'],
            'province'  => $root['province'],
            'city'  => $root['city'],
            'county'  => $root['county'],
            'address'  => $root['address'],
        ];
        //更新openid
        $original = (array)json_decode(cache($this->requestData['code']));
        (new \app\api\model\Member())->updateUser($root['id'],$original);
        //返回生成token
        return $Oauth2::generateToken($user);
    }

	/**
     *	用户注册
     */
    public function register()
    {
        (new LoginValidate())->checkRegisterParam();

        $data = [
            'mobile'=>trim($this->requestData['mobile']),
            'passwd'=>trim($this->requestData['password']),
            'nickname'=>trim($this->requestData['nickname']),
            'email'=>trim($this->requestData['email']),
            'company'=>trim($this->requestData['company']),
            'province'=>trim($this->requestData['province']),
            'city'=>trim($this->requestData['city']),
            'county'=>trim($this->requestData['county']),
            //'agent'=>trim($this->requestData['agent'])
        ];
        $ddd['name']=trim($this->requestData['agent']);
        $ddd['status']=1;
        $list=db("agent")->where($ddd)->find();
        if($list) {
            $data['agent'] = $list['id'];
        }
		$code = trim($this->requestData['code']);
        if (base64_encode($code) != Cache::get('code')) {
            return array('message'=>'验证码错误');
        }

        $memberDB = db('member');
        $condition['nickname'] = $data['nickname'];
        $member = $memberDB->where($condition)->find();
        if(!empty($member)) return array('message'=>'该昵称已被占用');

        unset($condition['nickname']);
        $condition['mobile'] = $data['mobile'];
        $member = $memberDB->where($condition)->find();
        if(!empty($member)) return array('message'=>'手机号码已注册');

        $file=Request::instance()->file('headimage');
        if($file){
            $info = $file->move(ROOT_PATH . 'public/static/uploads');
            if($info){
                $data['headimage']= request()->domain().'/static/uploads/'.str_replace('\\',"/",$info->getSaveName());
            }
        }
        $data['create_time']   = TIMESTAMP;
        $data['type']   = 1;//0
        $data['status']   = 0;//0
        $data['passwd']   = md5($data['passwd']);
        $insert = $memberDB->insert($data);
        if(!$insert) return array('message'=>'注册失败');
        return array();
    }

    /**
     * 忘记密码
     * @return array
     */
    public function forget()
    {
        (new LoginValidate())->checkForgetParam();

        $mobile   = $this->requestData['mobile'];
        $passwd   = $this->requestData['password'];
		$code     = $this->requestData['code'];
        if (base64_encode($code) != Cache::get('forget'))
            return array('message'=>'验证码错误');

        $where['mobile'] = $mobile;
        $root = db('member')->where($where)->find();
        if(!$root) return array('message'=>'不存在此账户');

        $where['mobile']  = $mobile;
        $data['passwd']   = md5($passwd);
        $update = db('member')->where($where)->update($data);
        if(!$update) return array('message'=>'修改失败');
        return array();
    }

}