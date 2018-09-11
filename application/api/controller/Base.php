<?php
/**
 * 控制器基类.
 * member: chan
 * Date: 2017/10/20 11:36
 */

namespace app\api\controller;
use think\Controller;
use think\Exception;
use think\Request;
class Base extends Controller
{
    public $request;
    public $requestData;//请求数据包
    public $aesData;//请求数据包
    protected $user;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->request = $request;
        $this->requestData = $request->param('');
        $this->request->__set('user',$this->user);
    }
    public function except()
    {

    }
    protected function checkPrimaryScope()
    {
        try{
            $userInfo = (new \app\api\service\Oauth2())->getUserInfoByToken(
                $this->request->param('access_token')
            );
            $this->user = $userInfo["user"];
            //权限控制（群主有封禁用户服务能力）
        }catch (Exception $e){
            $returnData = json_encode([
                'data' => array(),
                'code' => -1,
                'message' => $e->getMessage(),
                'errorCode' => $e->errorCode
            ]);
            exit($returnData);
        }
    }
    /*
     * 数据格式
     * @param $data array 结果数组
     * @param $code int (1表示success;-1表示fial)
     * @param message string  success or fial
     * return json数据
     */
    public function ajaxReturn($data = [],$code = 1,$message = 'success')
    {
        if (!is_array($data) || empty($data)){
            return json(['data'=>array(),'code'=>$code,'message'=>$message]);
        }
        if(array_key_exists('message',$data)){
            return json(['data'=>array(),'code'=>-1,'message'=>$data['message']]);
        }
        return json(['data'=> !array_key_exists(0,$data) ? array($data) : $data,'code'=>$code,'message'=>$message]);
    }


}