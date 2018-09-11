<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/12/7 16:50
 */

namespace app\api\controller;
use think\Controller;
use think\Cache;
use think\Request;

class Common extends Controller
{
    public $request;
    public $requestData;//请求数据包
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->request = $request;
        $this->requestData =  $request->param('');
    }

    protected function authorization()
    {
        if(Cache::get($this->requestData['code'])){
            $this->redirect('/api/Wechat/oauth',['baseUrl' => base64_encode($this->request->baseUrl())]);
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