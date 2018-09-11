<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/8/24  19:25
 */
namespace app\admin\Controller;
use EasyWeChat\Foundation\Application;
use think\Request;
use think\Db;
use think\Page;
class Order extends Common
{
    /**
     * 自动收货
     */
    public  function auto(){
        $list = db('order')->where('status=2 and refundid=0')->select();
       foreach($list as $v){
           if(time()-$v['create_time']-7*60*60*24>0){
               $id=$v['id'];
               $data['finish_time']=$v['create_time']+7*60*60*24;
               $data['status']=3;
               $list = db('order') ->where("id='$id'")->update($data);
               if($v['pay_type']==1){
                   if( $list['pay_type']==1) {
                       db("member")->where("id=" . $v['uid'])->setInc("credit",$list['price']);
                   }
               }

           }
       }
    }

    public function order_list()
    {
        $this->auto();
        $where=array();
        $status=input('status');
       
        if(!empty($status)){
            $where['o.status'] =$status;
        }
		 if(!empty(input('agentid'))) {
            $where['o.agentid'] = input('agentid');
            $where['o.status'] ='3';
        }
		 if(!empty(input('memberid'))) {
            $where['o.uid'] = input('memberid');
            $where['o.status'] ='3';
        }
        $ordersn=trim(input('ordersn'));
        if(!empty($ordersn)){//全部订单
            $where['o.order_sn'] =array("like",'%'.$ordersn.'%');
        }
        $member=trim(input('member'));
        if(!empty($member)){//全部订单
            $where['m.nickname|m.mobile'] =array("like",'%'.$member.'%');
        }
        $data=trim(input('data'));
        if(!empty($data)){//全部订单
            $where['o.name|o.mobile|o.province|o.city|o.county|o.address'] =array("like",'%'.$data.'%');
        }
        $starttime=trim(input('starttime'));
        if(!empty($starttime)){//全部订单
            $where['o.create_time'] =array("gt",strtotime($starttime));
        }
        $endtime=trim(input('endtime'));
        if(!empty($endtime)){//全部订单
            $where['o.create_time'] =array("gt",strtotime($endtime));
        }
        if(!empty($starttime) && !empty($endtime)){//全部订单
            $where['o.create_time'] =array("between",[strtotime($starttime),strtotime($endtime)]);
        }
		//var_dump( $where);exit;
        if(input('export')){
            $this->export($where);exit;
        }
        $list = db("order")->alias("o")
            ->field("o.*,m.nickname,m.mobile,m.headimage")
            ->join("member m","o.uid=m.id")
            ->where($where)
            ->order("id desc")
            ->paginate(5);
        $list->each(function($i){
            $i['order_goods']=db("order_goods")->where("order_id=".$i['id'])->select();
            return $i;
        });
        $count = db("order")->alias("o")
            ->field("o.*,m.nickname,m.mobile,m.headimage")
            ->join("member m","o.uid=m.id")
            ->where($where)
            ->count();
        $price = db("order")->alias("o")
            ->field("o.*,m.nickname,m.mobile,m.headimage")
            ->join("member m","o.uid=m.id")
            ->where($where)
            ->sum("price");
        $this->assign("list",$list);
        $this->assign("count",$count);
        $this->assign("price",$price);
        $this->assign("status",$status);
        $this->assign("ordersn",$ordersn);
        $this->assign("member",$member);
        $this->assign("data",$data);
        $this->assign("starttime",$starttime);
        $this->assign("endtime",$endtime);
        return $this->fetch();
    }
    /**
     * 订单列表导出excel
     */
    public function export($where)
    {

        $headArr = array("订单号","昵称","收货人姓名","收货号码","省份","城市","区/县","详细地址","下单时间","支付时间",
            '发货时间','完成时间',"商品名称","数量","规格","价格","状态");
        //$where['status']=1;
        $list = db("order")->alias("o")
            ->field("o.*,m.nickname,m.headimage,g.goods_name,g.goods_num,g.goods_price,g.spec_name")
            ->join("member m","o.uid=m.id")
            ->join("order_goods g","g.order_id=o.id")
            ->order("id desc")
            ->where($where)
            ->select();
        $data=array();
        foreach($list as $k => $v){
            $data[$k][] = $v['order_sn'];
            $data[$k][] = $v['nickname'];
            $data[$k][] = $v['name'];
            $data[$k][] = $v['mobile'];
            $data[$k][] = $v['province'];
            $data[$k][] = $v['city'];
            $data[$k][] = $v['county'];
            $data[$k][] = $v['address'];
            $data[$k][] = date("Y-m-d H:i",$v['create_time']);
            $data[$k][] =!empty($v['pay_time'])?date("Y-m-d H:i",$v['pay_time']):'';
            $data[$k][] = !empty($v['shipping_time'])?date("Y-m-d H:i",$v['shipping_time']):'';
            $data[$k][] = !empty($v['finish_time'])?date("Y-m-d H:i",$v['finish_time']):'';
            $data[$k][] = $v['goods_name'];
            $data[$k][] = $v['goods_num'];
            $data[$k][] = $v['spec_name'];
            $data[$k][] = $v['goods_price'];
            //,0待付款，1已付款，2已发货，3已完成,,4退款中，-2已关闭，-1已退款
            switch($v['status']){
                case '0': $data[$k][]='待付款';break;
                case '1': $data[$k][]='已付款';break;
                case '2': $data[$k][]='已发货';break;
                case '3': $data[$k][]='已完成';break;
                case '4': $data[$k][]='退款中';break;
                case '-1': $data[$k][]='已退款';break;
                case '-2': $data[$k][]='已关闭';break;
            }
        }
        $filename='订单列表';
        (new Export())->ImportExcel($filename,$headArr,$data);
    }
    public function close()
    {
        $id = input('id');
        if (!$id) {
            $this->error('参数有误');
        }
        $condition['order_id'] = $id;
        $list = db('order')->where($condition)->find();
        if (!$list) {
            $this->error('订单不存在');
        }
        if ($list['status']) {
            $this->error('非待付款订单不允许关闭');
        }
        $data['status'] = '-1';
        $data['cancel_time'] = time();
        $list = db('order')->where($condition)->update($data);
        $this->success('操作成功', U('Order/order_list'));
    }

    //后台支付订单
    public function pay()
    {
        $id = input('id');
        if (!$id) {
            $this->error('参数有误');
        }
        $condition['id'] = $id;
        $list = db('order')->where($condition)->find();
        if (!$list) {
            $this->error('订单不存在');
        }
        if ($list['status']) {
            $this->error('订单不是待支付状态，严禁操作');
        }
        $data['status'] = '1';
        $data['pay_type'] = 2;
        $data['pay_time'] = time();
        $list = db('order')->where($condition)->update($data);
        $this->success('操作成功', $_SERVER['HTTP_REFERER']);
    }

    //发货
    public function send()
    {
        $id = input('id');
        $condition['id'] = $id;
        if (Request::instance()->isPost()) {
			//$data['order_id'] =input('order_id');
            $data['shipping_num'] = input('express_sn');
            $data['shipping_code'] = input('express');
            $data['shipping_name'] = input('express_name');
            $data['shipping_time'] = time();
            $data['status'] = 2;
			
            $list = db('order')->where($condition)->update($data);
			if($list>0)  $this->success('操作成功', url('Order/order_list'));
        }
        if (!$id) {
            $this->error('参数有误');
        }
        $order = db('order')->where($condition)->find();
        if ($order['status'] != 1) {
            $this->error('不是待发货状态，非法访问');
        }
        $this->assign('list', $order);
        $this->assign('id', $id);
        return $this->fetch();
    }

    //查看快递信息
    public function express()
    {
        $id = input('id');
        $condition['id'] = $id;
        if (!$id) {
            $this->error('参数有误');
        }
        $order = db('order')->where($condition)->find();
        if ($order['status'] < 2) {
            $this->error('该订单未发货状态，非法访问');
        }
        $express = $this->getExpressList($order['shipping_code'], $order['shipping_num']);

        $express=$express['data'];
        $this->assign('res', $order);
        $this->assign('id', $id);
        $this->assign('express', $express);
        return $this->fetch();
    }
    public function getExpressList($express, $expresssn)
    {
        $url = "http://m.kuaidi100.com/query?type=" . $express . "&id=1&postid=" . $expresssn . "&temp=" . time();
        $list = file_get_contents($url);
        $list=json_decode($list,true);
        return $list;
    }
    //备注
    public function remark()
    {
        $id = input('id');
        $condition['id'] = $id;
        if (Request::instance()->isPost()) {
            $data['remark'] = input('remark');

            $list = db('order')->where($condition)->update($data);
            $this->success('操作成功');
        }
        if (!$id) {
            $this->error('参数有误');
        }
        $order = db('order')->field('remark')->where($condition)->find();
        $this->assign('res', $order);
        $this->assign('id', $id);
        return $this->fetch();
    }

    //订单详情
    public function detail()
    {
        $id = input('id');
        $condition['o.id'] = $id;
        if (Request::instance()->isPost()) {
            $data['remark'] = input('remark');
            $list = db('order')->where($condition)->update($data);
            $this->success('操作成功', $_SERVER['HTTP_REFERER']);
        }
        if (!$id) {
            $this->error('参数有误');
        }
        $order = db('order')->alias('o')->join('member m', 'o.uid=m.id')
            ->field('m.nickname,m.mobile,o.*')
            ->where($condition)
            ->find();
        $where['o.id'] = $id;
        $order_goods = db('order')->alias('o')
            ->field('g.goods_name,g.goods_num,g.goods_price,g.market_price,spec_name,g.goods_image,o.send_fee,o.coupon_money')
            ->join('order_goods g','g.order_id=o.id')
            ->where($where)
            ->select();
        foreach($order_goods as &$v){
            $v['goods_image']=IMG_PATH.$v['goods_image'];
        }
        unset($v);
        $this->assign('order_goods', $order_goods);
        $this->assign('res', $order);
        $this->assign('id', $id);
        return $this->fetch();
    }
/*    //修改收货地址
    public function address()
    {
        $id = input('id');
        $condition['id'] = $id;
        if (Request::instance()->isPost()) {
            $data['remark'] = input('remark');

            $list = db('order')->where($condition)->save($data);
            $this->success('操作成功', url('Order/order_list'));
        }

        if (!$id) {
            $this->error('参数有误');
        }
        $order = db('order')->alias('o')->join('member m', 'o.user_id=m.memid')
            ->where($condition)
            ->find();
        $this->assign('res', $order);
        $this->assign('id', $id);
        return $this->fetch();
    }*/

    //退货（退款）
    public function refund()
    {
        $id = input('refundid');
        if (!$id) {
            $this->error('参数有误');
        }
        $submit=input('submit');
        if (Request::instance()->isPost()) {
            $condition['r.id'] = $id;
            $data['reply'] = input('reply');
            if($submit=='允许退货'){
                $data['status'] =1;
            }
            if($submit=='拒绝申请'){
                $data['status'] =-1;
            }
             if($submit=='确认退款') {
                 $data['status'] =3;

                 $order = db('order')->alias('o')->join('order_refund r', 'o.id=r.order_id')
                     ->where($condition)
                     ->find();
                 if($order['status']==-1){
                     $this->error('订单已关闭，不能操作');
                 }
                 $order_goods = db('order_goods')->where(['order_id' => $order['id']])->select();
                 if (empty($order_goods))   $this->error('不能操作');

                 Db::startTrans();
                 try {
                     if (!db('order')->where(['id' => $order['id']])->update(['status' =>'-1', 'cancel_time' => TIMESTAMP])) {
                         throw new \Exception('退款订单失败');
                     }
                     $model_goods = db('goods');
                     $model_spec = db('goods_option_value');
                     foreach ($order_goods as $g) {
                         if($g['spec_id'] > 0){
                             if($model_spec->where(['id' => $g['spec_id']])->setInc('stock',$g['goods_num']) === false){
                                 throw new \Exception('返回规格库存失败');
                             }
                         }
                         if ($model_goods->where(['id' => $g['goods_id']])->setDec('sales_num', $g['goods_num']) === false){
                             throw new \Exception('返回减销量失败');
                         }
                     }
                     if( $order['pay_type']==1) {
                        $credit= db("member")->where("id=" . $order['uid'])->setInc("credit",$order['price']);
                         if(!$credit) throw new \Exception('返信用额失败');
                     }
                     Db::commit();
                 } catch (\Exception $e) {
                     Db::rollback();
                     $this->error( $e->getMessage());
                 }
             }
            $res=db('order_refund')->alias("r")->where($condition)->update($data);
            $this->success('操作成功',url('order/refund',array('refundid'=>$id)));

        }
      $condition['r.id'] = $id;
      $condition['o.status']=array("gt",0);
      //  var_dump($condition);
        $order = db('order')->field('o.*,r.*,r.status as rstatus')->alias('o')->join('order_refund r', 'o.id=r.order_id')
            ->where($condition)
            //->where($ddd)
            ->find();
        if($order['express_sn']){
            $express=$this->getExpressList($order['express_code'],$order['express_sn']);
            $this->assign('express', $express['data']);
        }
        $this->assign('list', $order);
        $this->assign('id', $id);
        return $this->fetch();
    }

    public function refundtest()
    {
        $options = [
            // 前面的appid什么的也得保留哦
            'app_id' =>config("wx.app_id"),
            'payment' => [
                'merchant_id'        => config("wx.mch_id"),
                'key'                => config("wx.key"),
                'cert_path'          =>EXTEND_PATH. 'cert\apiclient_cert.pem', // XXX: 绝对路径！！！！
                'key_path'           => EXTEND_PATH.'cert\apiclient_key.pem',      // XXX: 绝对路径！！！！
                'notify_url'         => '默认的订单回调地址',       // 你也可以在下单时单独设置来想覆盖它
            ],
        ];
        $app = new Application($options);
        $payment = $app->payment;
        $order_sn=input("order_sn");
        $refund_sn=time();
        $money=input("money")*100;
        $result = $payment->refund($order_sn, $refund_sn, $money);
        if($result->result_code=='SUCCESS'){
            echo true;
        }else{
            echo false;
        }
    }

   /* //拒绝退货（退款）
    public function refuse()
    {
        $id = input('id');
        $condition['refund_id'] = $id;
        if (Request::instance()->isPost()) {
            $data['remark'] = input('remark');
            $this->success('操作成功', url('Order/order_list'));
        }
        if (!$id) {
            $this->error('参数有误');
        }
        $list = db('order')->where($condition)->update($data);

        $this->assign('list', $list);
        $this->assign('id', $id);
        return $this->fetch();
    }*/
}
