<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/11/9  19:27
 */
namespace app\api\logic;
use app\api\BaseModel;
use app\api\controller\Wechat;
use think\Db;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order as WXorder;
class Pay extends  BaseModel{

    /**
     * app
     */
    public function app()
    {
        $options = [
            'app_id' => config("wx.app_id"),
            'secret' => config('wx.app_secret'),
            'token'  => config('wx.token'),
            'log' => [
                'level'      => 'debug',
                'permission' => 0777,
                'file'       => '/tmp/easywechat.log',
            ],
            'payment' => [
                'merchant_id'        =>config("wx.mch_id"),
                'key'                =>  config("wx.key"),
                'cert_path'          =>EXTEND_PATH. 'cert\apiclient_cert.pem', // XXX: 绝对路径！！！！
                'key_path'           => EXTEND_PATH.'cert\apiclient_key.pem',      // XXX: 绝对路径！！！！
                'notify_url'       => request()->domain().'/api/pay/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            ],
        ];
        return  new Application($options);
    }

    public function pay()
    {
        // $app = (new Wechat())->app();

        $db_order = db('order');

        $where['id']=$this->requestData['id'];
        $where['uid']=$this->user['id'];
        $list=$db_order->where($where)->find();
        $member=db("member")->where("id=".$list['uid'])->find();
        if(!$list) return array('message'=>'订单不存在');
        if($list['status']!=0)  return array('message'=>'不是待支付状态');
        $app=$this->app();
        $payment = $app->payment;
        $attributes = [
            'trade_type'       => 'JSAPI', // JSAPI，NATIVE，APP...
            'body'             => '集杰天下',
            'detail'           => '订单号：'.$list['order_sn'],
            'out_trade_no'     => $list['order_sn'],
            'total_fee'        => $list['price']*100, // 单位：分
            'notify_url'       => request()->domain().'/api/pay/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $member['wx_openid'], // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
            // ...
        ];

        $order = new WXorder($attributes);

        $result = $payment->prepare($order);

        if ($result->return_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
            $json = $payment->configForPayment($prepayId); // 返回 json 字符串，如果想返回数组，传第二个参数 false
            return array('jsapi'=>$json);
        }elseif($result->return_code == 'FAIL'){
            return array("message"=>$result->return_msg);
        }
    }
    /**
     * 检查目录是否可写
     * @param  string   $path    目录
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            $this->error = "目录 {$path} 创建失败！";
            return false;
        }
    }
    public function notify(){
        $xml = file_get_contents('php://input');
        if(!$xml){
            exit('非法访问');
        }
        $path=ROOT_PATH . 'public' . DS .'static' . DS  . 'log' . DS  .date('Ymd') ;
        if (false !== $this->checkPath($path)) {
            $paths=$path.'/'.'log.txt';
             file_put_contents($paths,$xml,FILE_APPEND);
        }
        $wx=$this->xmlToArray($xml);
        ksort($wx);// 对数据进行排序
        $str =$this-> ToUrlParams($wx);//对数据拼接成字符串
        $user_sign = strtoupper(md5($str));
        if($user_sign == $wx['sign']){//验证成功
            $log=db("order")->where("order_sn='".$wx['out_trade_no']."'")->find();

            if(!empty($log) && $log['status'] == '0' && (($wx['total_fee'] / 100) == $log['price'])) {//订单存在并且没支付状态，金额一致
                Db::startTrans();
                try{
                    $res=db("order")->where("order_sn='".$wx['out_trade_no']."'")->update(array("status"=>1,'pay_time'=>NOW_TIME));
                    $data=array(
                        'content'=>'亲，您的订单已支付，请等候发货',
                        'uid'=>$log['uid'],
                        'type'=>0,
                        'create_time'=>NOW_TIME,
                        'relation_id'=>$log['id'],
                    );
                    $add=db("news_log")->insert($data);
                    $member=checkMember($log['uid']);

                    $app=$this->app();
                    if($member['agent']){
                        $ddd['id']=$member['agent'];
                        $ddd['status']=1;
                        $agentmemebr=db("agent")->where($ddd)->find();
                        $agent=checkMember($agentmemebr['uid']);

                        $data = array(
                            'touser' => $agent['wx_openid'],
                            'template_id' => '06onil_9EAndMF3zV8hmZYHjhswt0eTRZ4xwrT1My4g',
                            'url' => '',
                            'data' => [
                                'first' => '代理订单支付成功通知',
                                'keyword1' => $log['order_sn'],
                                'keyword2' =>date("Y-m-d",$log['create_time']),
                                'keyword3' =>$log['province'].$log['city'].$log['county'].$log['address'],
                                'keyword4' =>$log['name'].'-'.$log['mobile'],
                                'keyword5'=>'已支付',
                                'remark' => '请及时为买家发货'
                            ]
                        );
                        $app->notice->send($data);

                    }

                    $data = array(
                        'touser' => $member['wx_openid'],
                        'template_id' => '06onil_9EAndMF3zV8hmZYHjhswt0eTRZ4xwrT1My4g',
                        'url' => request()->domain() . '/wxjjtx/#/orderDetail?id='.$log['id'],
                        'data' => [
                            'first' => '订单支付成功通知',
                            'keyword1' => $log['order_sn'],
                            'keyword2' =>date("Y-m-d",$log['create_time']),
                            'keyword3' =>$log['province'].$log['city'].$log['county'].$log['address'],
                            'keyword4' =>$log['name'].'-'.$log['mobile'],
                            'keyword5'=>'已支付',
                            'remark' => '请耐心等待卖家发货，祝你购物愉快！'
                        ]
                    );
                    $app->notice->send($data);


                    Db::commit();
                } catch (\Exception $e) {// 回滚事务
                    Db::rollback();
                    echo   $e->getMessage();
                    exit('fail');
                }
                if( $res) {
                    exit('success');
                }else{
                    exit('fail');
                }
            }
            exit('fail');
        }
        exit('fail');
    }
    public  function xmlToArray($xml)
    {

        //禁止引用外部xml实体

        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }
    /**
     * 格式化参数格式化成url参数
     */
    private function ToUrlParams($arr)
    {
        $weipay_key = config("wx.key");//微信的key,这个是微信支付给你的key，不要瞎填。
        $buff = "";
        foreach ($arr as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff.'&key='.$weipay_key;
    }

}