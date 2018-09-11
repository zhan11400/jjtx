<?php
/**
 * Class descript.
 * User: 广州利大科技 chan
 * Date: 2017/8/7 19:31
 */

namespace app\api\logic;

use app\api\model\Order as OrderModel;
use think\Model;
use think\Request;
use think\Db;
use app\api\BaseModel;

class Order extends BaseModel
{

    protected $model_order;

    public function __construct()
    {
        parent::__construct();
        $this->model_order = db('order');
    }

    //订单列表
    public function order_list()
    {
        $memid = $this->user['id'];
        if (isset($this->requestData['status'])) {//全部订单
            $condition['status'] =$this->requestData['status'];
            if($this->requestData['status']==5){
                $condition['status']=array("in",'4,-1');
            }
        } else {
            $condition['status'] = array('gt', -2);
        }
        $condition['uid'] = $memid;
        $condition['isdeleted'] = 0;
        $p = isset($this->requestData['page']) ? $this->requestData['page'] : '1';
        $size = isset($this->requestData['limit']) ? $this->requestData['limit'] : '10';
        $list = db('order')->field('id,refundid,pay_time,order_sn,status,isremind,pay_type,send_type,send_fee,price')
            ->where($condition)
            ->order('create_time desc')
            ->page($p, $size)
            ->select();
        foreach ($list as &$v) {
            $orderid = $v['id'];
            $v['goods'] = db('order_goods')
                ->field('goods_name,goods_num,market_price,goods_price,spec_name,goods_image')
                ->where("order_id='$orderid'")
                ->select();
            $totalcount = 0;
            $totalprice = 0;
            foreach ($v['goods'] as $k => $item) {
                $v['goods'][$k]['goods_image'] = IMG_PATH.$item['goods_image'];
                $totalcount += $item['goods_num'];
                $totalprice += $item['goods_price'] * $item['goods_num'];
            }
            $v['totalcount'] = $totalcount;
            $v['totalprice'] = $totalprice;
        }
        unset($v);
        $count = db('order')->field('id,refundid,pay_time,order_sn,status')
            ->where($condition)
            ->count();
        $result = array(
            'order' => $list,
            'page_num' => $p,
            'page_limit' => $size,
            'count' => $count
        );
        return $result;
    }

    //24小时没发货，可以提醒发货
    public function remind()
    {
        $memid = $this->user['id'];
        $id = $this->requestData['id'];
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $memid;
        $list = db('order')->where($condition)->find();
        if (!$list) return array('message' => '订单不存在');
        if ($list['status'] != 1) return array('message' => '非待发货状态');
        if ($list['isremind'] == 1) return array('message' => '已提醒过了');
        if (time() - $list['pay_time'] < 24 * 60 * 60) return array('message' => '没到提醒发货时间');
        $res = db('order')->where("id='$id'")->update(array('isremind' => 1));
        if ($res === false) return array("message" => '系统繁忙，稍后再试');

    }

    //查看订单详情
    public function order_detail()
    {
        $condition['uid'] = $this->user['id'];
        $condition['id'] = $this->requestData['id'];
        $list = db('order')->where($condition)->find();
        if (empty($list)) return array('message' => '订单不存在');
        $where['order_id'] = $list['id'];
        $list['order_goods'] = db('order_goods')->where($where)->select();
        if ($list['create_time']) {
            $list['create_time'] = date("Y-m-d H:i:s", $list['create_time']);
        }
        if ($list['shipping_time']) {
            $list['shipping_time'] = date("Y-m-d H:i:s", $list['shipping_time']);
        }
        if ($list['cancel_time']) {
            $list['cancel_time'] = date("Y-m-d H:i:s", $list['cancel_time']);
        }
        if ($list['finish_time']) {
            $list['finish_time'] = date("Y-m-d H:i:s", $list['finish_time']);
        }
        if ($list['pay_time']) {
            $list['pay_time'] = date("Y-m-d H:i:s", $list['pay_time']);
        }
        foreach ($list['order_goods'] as &$item) {
            $item['goods_image'] = IMG_PATH . $item['goods_image'];
        }
        return $list;
    }

    //查看快递信息
    public function express()
    {
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $order = db('order')->where($condition)->find();
        if ($order['status'] < 2) {
            return array('message' => '该订单未发货状态，非法访问');
        }
        $express = $this->getExpressList($order['shipping_code'], $order['shipping_num']);
        if ($express['status'] != 200) {
            return array();
        }
        $data = $express['data'];
        return $data;
    }

    public function getExpressList($express, $expresssn)
    {
        $url = "http://m.kuaidi100.com/query?type=" . $express . "&id=1&postid=" . $expresssn . "&temp=" . time();
        $list = file_get_contents($url);
        $list = json_decode($list, true);
        return $list;
    }

    /**
     * 确定订单
    */
     public function order_confirm()
    {

        $tnumber=0;
        $memid = $this->user['id'];
        $member=checkMember($memid);
        $UserStatus=$this->checkUserStatus();
        if($member['agent']){
            $agent=db("agent")->field("name")->where('id='.$member['agent'])->find();
            $agent['percent']=$UserStatus['percent'];
        }else{
            $agent=array('name'=>'总平台','percent'=>1);
        }
            $config=db("config")->where("id=3")->find();
            $data['send_fee']= floatval($config['value']);

        $data['agent'] =$agent;
        $addressid =isset($this->requestData['add_id'])?$this->requestData['add_id']:'0';
        if ($addressid!='0') {
            $add = db('user_address')->where("address_id='$addressid' and user_id='$memid'")->find();
            $data['address'] = (!empty($add)) ? $add : array();
        } else {
            $add = db('user_address')->where("isdefault=1 and user_id='$memid'")->find();
            $data['address'] = (!empty($add)) ? $add : array();
        }
        if (isset($this->requestData['specid'])) {//有商品id，就是直接购买
            $total=isset($this->requestData['total'])?$this->requestData['total']:'1';
            $specid=$this->requestData['specid'];
            $goods = db('goods_option_value')->alias('p')
                    ->field('g.name as goodsname,g.image,p.*')
                    ->join('goods g', 'g.id=p.goodsid')
                    ->where("p.id='$specid'")
                    ->select();
            $goods[0]['image'] = IMG_PATH.$goods[0]['image'];
            $goods[0]['total'] = $total;
			 $mao= $this->getmao($goods[0]['goodsid']);
            $goods[0]['price']=$goods[0]['price'] * $UserStatus['percent']*$mao;
            $price = floor($goods[0]['price']*$total * 100) / 100;
            $data['list'] = $goods;
            $data['total'] = $total;
            $data['price'] = $price;

        } else {//没有商品id，就是来自购物车
            $cart = db('cart')->alias('c')
                ->field('g.name as goodsname,g.image,p.*,c.total')
                ->join('goods_option_value p', 'c.specid=p.id')
                ->join('goods g', 'g.id=c.goodsid')
                ->where("uid='$memid' and isselect=1")
                ->select();
            if (!$cart) return array('message' => '购物车为空！');
            $price=0;
            foreach($cart as &$v){
                $v['image'] = IMG_PATH.$v['image'];
                $tnumber +=$v['total'];
				 $mao= $this->getmao($v['goodsid']);
                $v['price'] = floor($v['price'] * $UserStatus['percent'] * 100*$mao) / 100;
                $price += $v['price'] * $v['total'];
            }
            $data['list'] = $cart;
            $data['total'] = $tnumber;
            $data['price'] = $price;
        }
        $coupon['user_id']=$this->user['id'];
        $coupon['status']=0;
        $list = db('mycoupon')->where($coupon)->select();
        $dcoupon=array();
        foreach($list as &$v){
            if($v['status']==0){
                if($v['end_time']<TIMESTAMP){
                    db('mycoupon')->where(['user_id' =>$this->user['id'],'id'=>$v['id']])->update(array('status'=>-1));
                    $v['status']=-1;
                }
            }
            if($v['status']==0 && $v['fullmoney']<$price){
             $dcoupon[]=$v;
            }
        }
        $data['coupon']=$dcoupon;
        return $data;
    }

    //获取收货地址
    public function getAddress($memid, $addressid)
    {
        if (!$addressid)  return array('message'=>'请选择收货地址');
        $address = db('user_address')->where("user_id='$memid' and address_id='$addressid'")->find();
        if (!$address)  return array('message'=>'收货地址不存在');
        return $address;
    }

    public function checkgoods($specid, $total)
    {
        $goods = db('goods_option_value')->alias('p')
            ->field('g.name as goodsname,g.status,g.image,p.*')
            ->join('goods g', 'g.id=p.goodsid')
            ->where("p.id='$specid'")
            ->find();
        if ($goods['status'] != 1)return array('message'=> $goods['goodsname'] . '已下架');
        if (!$goods['stock'] || $total > $goods['stock']) {
            $result['message'] = $goods['goodsname'] . $goods['spec_name'] . '库存不足，最多能购买' . $goods['stock'] . '件';
            return $result;
        }
        return $goods;
    }

    /**
     * 生成唯一订单号
     */
    public function build_order_no($p)
    {
        $no = $p . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 4);
        //检测是否存在
        $info = db('order')->where(array('order_sn' => $no))->find();
        (!empty($info)) && $no = $this->build_order_no($p);
        return $no;
    }

    //提交订单
    public function order_create()
    {
        $memid =$this->user['id'];
        $UserStatus=$this->checkUserStatus();
        $addressid = $this->requestData['addressid'];
        $address = $this->getAddress($memid, $addressid);//获取收货地址
        if (array_key_exists('message', $address)) {
            return $address;
        }
        $member=checkMember($memid);
        $percent=$UserStatus['percent'];
        $data['remark']=isset($this->requestData['remark'])?$this->requestData['remark']:'';
        $data['send_type']=isset($this->requestData['send_type'])?$this->requestData['send_type']:0;
        if($data['send_type']==1){
            $config=db("config")->where("id=3")->find();
            $data['send_fee']= $config['value'];
        }
        $data['pay_type']=isset($this->requestData['pay_type'])?$this->requestData['pay_type']:0;
        $data['agentid'] =$member['agent'];
        $data['province'] = $address['province'];
        $data['city'] = $address['city'];
        $data['county'] = $address['country'];
        $data['name'] = $address['name'];
        $data['address'] = $address['detail'];
        $data['mobile'] = $address['mobile'];
        $data['order_sn'] = $this->build_order_no('SH');//主订单号
        $data['coupon_id']=isset($this->requestData['coupon_id'])?$this->requestData['coupon_id']:0;
        if($data['pay_type']==1){
            $data['status'] ='1';
            $content='亲，您的货到付款订单'.$data['order_sn'].'已提交，请等待发货';
        }else{
            $content='亲，您的订单'.$data['order_sn'].'已提交，请及时支付';
        }
        //$data['status'] = 0;//0未完成，1已付款，3已收货，2已发货'
        $data['create_time'] = time();
        $data['uid'] = $memid;
        $specid =isset($this->requestData['specid'])?$this->requestData['specid']:0;
        $per=$this->getCostprice();
        if (!empty($specid)) {
            $total=isset($this->requestData['total'])?$this->requestData['total']:1;
            $goods = $this->checkgoods($specid, $total);//判断是否下架，库存是否足够
            if (array_key_exists('message', $goods)) {
                return $goods;
            }
            $mao= $this->getmao($goods['goodsid']);
            $price = floor($goods['price'] * $percent*$total*$mao * 100) / 100;//当前价格
            $data['costprice'] = floor($goods['price'] * $per*$total*$mao * 100) / 100;//成本价,不加入快递费
            if($data['send_type']==1){
                $config=db("config")->where("id=3")->find();
                $data['send_fee']= $config['value'];
                $data['price'] = $price+$data['send_fee'];//订单总价()
            }else{
                $data['price'] = $price;//订单总价()
            }
            if($data['pay_type']==1) {
                if($data['price']>$member['credit']){
                    return array('message' => '信用额不足！');
                }
            }
            if( $data['coupon_id']>0) {
                $coupon = db('mycoupon')->where("id=" . $data['coupon_id'])->find();
                $data['coupon_money']=$coupon['money'];
                $data['price']=$data['price']- $data['coupon_money'];
            }
            $order_goods['goods_id'] = $goods['goodsid'];//商品id
            $order_goods['goods_name'] = $goods['goodsname'];//商品名字
            $order_goods['goods_image'] = $goods['image'];//商品图片
            $order_goods['goods_num'] = $total;//购买数量
            $order_goods['market_price'] = $goods['oldprice'];//市场价格
            $order_goods['goods_price'] = floor($goods['price'] * $percent*$mao * 100) / 100;//当前价格

           // exit;
            //$order_goods['cost_price'] = floor($goods['price'] * $per*$mao * 100) / 100;//当前价格
            $order_goods['spec_id'] = intval($specid);//规格id
            $order_goods['spec_name'] = $goods['spec_name'];//规格名称
            try {
                $insert_id = db("order")->insertGetId($data);
                if (!$insert_id) {
                    throw new \Exception('下单错误');
                }
                    $res = db('goods_option_value')->where('id='.$specid)->setDec('stock', $total);//减库存
                    if (!$res) {
                        throw new \Exception('减商品库存失败');
                    }
                $res = db('goods')->where("id=".$goods['goodsid'])->setInc('sales_num', $total);//增加销量
                if (!$res) {
                    throw new \Exception('增加销量失败');
                }
                $order_goods['order_id'] = $insert_id;
                db('order_goods')->insert($order_goods);

                $eee=array(
                    'content'=>$content,
                    'uid'=>$this->user['id'],
                    'type'=>0,
                    'create_time'=>NOW_TIME,
                    'relation_id'=>$insert_id,
                );
                $add=db("news_log")->insert($eee);
                if( $data['coupon_id']>0) {
                    db("mycoupon")->where("id=" . $data['coupon_id'])->update(array("status" => -1));
                }
                if($data['pay_type']==1) {
                   db("member")->where("id=".$member['id'])->setDec("credit",$data['price']);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return array("meaasge"=> $e->getMessage());
            }
            return array('pay_type'=>$data['pay_type'],'order_id' =>$insert_id,'order_sn'=>$data['order_sn']);
        } else {

            $cart = db('cart')->alias('c')
                ->field('g.name as goodsname,g.image,p.*,c.total,c.specid')
                ->join('goods_option_value p', 'c.specid=p.id')
                ->join('goods g', 'g.id=c.goodsid')
                ->where("uid='$memid' and isselect=1")
                ->select();
            if (!$cart) return array('message' => '购物车为空！');
            $price = 0;
            $costprice=0;
            foreach ($cart as $key => $v) {
                $goods = $this->checkgoods($v['specid'], $v['total']);
                if (array_key_exists('message', $goods)) {
                    return $goods;
                }
                $mao= $this->getmao($goods['goodsid']);
                $price += floor($goods['price'] * $percent * $v['total']*$mao * 100) / 100;
               $costprice+= floor($goods['price'] * $per*$v['total']*$mao * 100) / 100;//成本价,不加入快递费
            }
            $data['costprice'] =$costprice;
            if($data['send_type']==1){
                $config=db("config")->where("id=3")->find();
                $data['send_fee']= $config['value'];
                $data['price'] = $price+$data['send_fee'];//订单总价()
            }else{
                $data['price'] = $price;//订单总价()
            }
            if($data['pay_type']==1) {
                if($data['price']>$member['credit']){
                    return array('message' => '信用额不足！');
                }
            }
            if( $data['coupon_id']>0) {
                $coupon = db('mycoupon')->where("id=" . $data['coupon_id'])->find();
                $data['coupon_money']=$coupon['money'];
                $data['price']=$data['price']- $data['coupon_money'];
            }
            Db::startTrans();
            try {
                $insert_id = db("order")->insertGetId($data);
                if ($insert_id > 0) {
                    //添加订单商品
                    $this->addOrderGoods($insert_id, $memid,$percent);
                    $eee=array(
                        'content'=>$content,
                        'uid'=>$this->user['id'],
                        'type'=>0,
                        'create_time'=>NOW_TIME,
                        'relation_id'=>$insert_id,
                    );
                    $add=db("news_log")->insert($eee);
                    if( $data['coupon_id']>0) {
                        db("mycoupon")->where("id=" . $data['coupon_id'])->update(array("status" => -1));
                    }
                    if($data['pay_type']==1) {
                        db("member")->where("id=".$member['id'])->setDec("credit",$data['price']);
                    }
                    //添加成功之后清空购物车
                    db("cart")->where("uid='$memid' and isselect=1")->delete();
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $result['message'] = $e->getMessage();
                return $result;
            }
            return array('pay_type'=>$data['pay_type'],'order_id' =>$insert_id,'order_sn'=>$data['order_sn']);
        }

    }

    public function getOrderByOrderID($id)
    {
        //查询订单
    }

    //回收失效订单商品数量
    private function recycleOrderProductQuantity()
    {

    }

    private function addOrderGoods($insertid, $memid,$percent)
    {

        $shop=db('goods');
        $goods_spec=db('goods_option_value');
        $cart = db('cart')->alias('c')
            ->field('g.name as goodsname,g.image,p.*,c.total,c.specid')
            ->join('goods_option_value p', 'c.specid=p.id')
            ->join('goods g', 'g.id=c.goodsid')
            ->where("uid='$memid' and isselect=1")
            ->select();
        foreach ($cart as $k => $d) {
            $res = $goods_spec->where("id= " . $d['id'])->setDec('stock', $d['total']);//减库存
            if (!$res) throw new \Exception('减库存失败');
            $res = $shop->where("id=" . $d['goodsid'])->setInc('sales_num', $d['total']);//增加销量
            if (!$res) throw new \Exception('增加销量失败');
            $order_goods['order_id'] = $insertid;
            $order_goods['goods_id'] = $d['goodsid'];//商品id
            $order_goods['goods_name'] = $d['goodsname'];//商品名字
            $order_goods['goods_image'] = $d['image'];//商品图片
            $order_goods['goods_num'] = $d['total'];//购买数量
            $order_goods['goods_price'] = floor($d['price'] * $percent * 100)/100;//
            $order_goods['market_price'] = $d['oldprice'];//市场价格
            $order_goods['spec_id'] = $d['id'];//规格id
            $order_goods['spec_name'] = $d['spec_name'];//规格名称
            //  var_dump($order_goods);
            db('order_goods')->insert($order_goods);
        }
    }
    /**
     * 取消订单
     */
    public function order_cancel()
    {
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $list = $this->model_order->where($condition)->find();
        if(!$list) return array('message'=>'订单不存在');
        if ($list['status'] !='0') return array('message' => '非待支付状态，不能取消订单');
        $order_goods = db('order_goods')->where(['order_id' => $list['id']])->select();
        if (empty($order_goods)) return array('message' => '取消订单失败!');
        Db::startTrans();
        try {
            if (!$this->model_order->where(['id' => $list['id']])->update(['status' =>'-2', 'cancel_time' => TIMESTAMP])) {
                throw new \Exception('取消订单失败');
            }
            if( $list['coupon_id']>0) {
                db("mycoupon")->where("id=" . $list['coupon_id'])->update(array("status" =>0));
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
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array('message' => $e->getMessage());
        }

        return array();

    }
    /**
     * 确认收货
     */
    public function order_receive()
    {
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $order = $this->model_order->where($condition)->find();
        if(!$order) return array('message'=>'订单不存在');
        if ($order['status'] !== '2') return array('message' => '非待收货状态');
        if (!$this->model_order->where(['id' => $order['id']])->update(['status' => 3, 'finish_time' => TIMESTAMP])) {
            return array('message' => '确认收货失败');
        }
        if( $order['pay_type']==1) {
            db("member")->where("id=" . $order['uid'])->setInc("credit",$order['price']);
        }
        return array();
    }

    /**
     * delete
     */
    public function order_deleted()
    {
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $order = $this->model_order->where($condition)->find();
        if(!$order) return array('message'=>'订单不存在');
        if ($order['status'] != 3) return array('message' => '未完成订单不能删除订单');
        if (!$this->model_order->where(['id' => $order['id']])->update(['isdeleted' =>1])) {
            return array('message' => '删除失败');
        }
        return array();

    }
    /**
     * 订单退款
     */
    public function order_refund()
    {
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $order = $this->model_order->where($condition)->find();
        if(!$order) return array('message'=>'订单不存在');
        if (!in_array($order['status'], [1, 2])) return array('message' => '该订单未满足退款条件');
        $type=isset($this->requestData['type'])?$this->requestData['type']:1;
        if (!in_array($type, array(1, 2))) { //1退款，2退款退货
            return array('message' => '退款类型有误');
        }
        if($order['pay_type']==1) return array('message' => '货到付款不支持退款');
        if($order['status']==1 && $type==2)  return array('message' => '不是发货状态，不能退货');
        $content = !isset($this->requestData['content']) ? '' :$this->requestData['content'];
        $content = htmlspecialchars($content);
        $files = request()->file('images');
        $images=array();
        if($files) {
            foreach ($files as $file) {
                $info = $file->move(ROOT_PATH . 'public/static/uploads');
                if ($info) {
                    $images[] = $info->getSaveName();
                }
            }
        }
        $data['images']=serialize($images);
        $data['uid']= $order['uid'];
        $data['order_id']= $order['id'];
        $data['type']=$type;
        $data['content']=$content;
        $data['create_time']=TIMESTAMP;
        Db::startTrans();
        try {
            $refund = db('order_refund')->insertGetId($data);
            if (!$refund) {
                throw new \Exception('添加退款记录失败');
            }
            //更新订单状态
            if (!$this->model_order->where(['id' => $order['id']])->update(['refundid' => $refund,'status'=>4])) {
                throw new \Exception('修改订单状态');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array('message' => $e->getMessage());
        }
        return array();

    }
    /**
     * 申请退款详情
     */
    public function order_refund_detail(){
        $condition['id'] = $this->requestData['id'];
        $condition['uid'] = $this->user['id'];
        $refund = db('order_refund')->where($condition)->find();
        if(!$refund) return array("message"=>'记录不存在');
        $refund['create_time']=date("Y-m-d H:i:s", $refund['create_time']);
       $images=unserialize($refund['images']);
        if($images){
            foreach($images as &$v){
                $v=IMG_PATH.$v;
            }
        }
        $refund['images']=$images;
        $refund['content']=htmlspecialchars_decode($refund['content']);
        if($refund['check_time']>0) {
            $refund['check_time'] = date("Y-m-d H:i:s", $refund['check_time']);
        }
        return $refund;
    }
    /**
     * 评价信息
     */
    public function evaluate_info(){
        $request = $this->request->requestData();
        $member_id = $request['memid'];
        $order_id = $request['order_id'];
        $order = $this->checkOrder($order_id,$member_id);
        if(isset($order['message'])) return $order;
        if ($order['status'] !== ORDER_STATUS_SUCCESS) return array('message' => '该订单未满足评价条件');
        if ($order['iscomment'] == ORDER_IS_COMMENT_YES) return array('message' => '该订单已评价');
        $model_comment = db('goods_comment');
        $goods_comment = $model_comment->where(['order_id' => $order['order_id']])->select();
        if (!empty($goods_comment)) {
            $this->model_order->where(['order_id' => $order['order_id']])->save(['iscomment' => ORDER_IS_COMMENT_YES]);
            return array('message' => '该订单已评价');
        }
        $order_goods = db('order_goods')->field('order_id,goods_id,spec_id,goods_name,spec_name')->where(['order_id' => $order['order_id']])->select();
        return $order_goods;


    }
    /**
     * 订单评价(wap端专属)
     */
    public function order_comment()
    {
        $request = $this->request->requestData();
        $member_id = $request['memid'];
        $order_id = $request['order_id'];
        $order = $this->checkOrder($order_id,$member_id);
        if(isset($order['message'])) return $order;
        if ($order['status'] !== ORDER_STATUS_SUCCESS) return array('message' => '该订单未满足评价条件');
        if ($order['iscomment'] == ORDER_IS_COMMENT_YES) return array('message' => '该订单已评价');
        $model_comment = db('goods_comment');
        $goods_comment = $model_comment->where(['order_id' => $order['order_id']])->select();
        if (!empty($goods_comment)) {
            $this->model_order->where(['order_id' => $order['order']])->save(['iscomment' => ORDER_IS_COMMENT_YES]);
            return array('message' => '该订单已评价');
        }

        $order_goods = db('order_goods')->field('goods_id,spec_name')->where(['order_id' => $order['order_id']])->select();
        $order_goods_ids = array_column($order_goods, 'goods_id');
        $con = $request['commemt'];
        foreach ($order_goods_ids as $k=>$v) {
            $comment[$k]['goods_id']=$v;
            $comment[$k]['content']=$con[$k];
        }
        //上传图片
        $images = array();
        $files = request()->file();
        if (!empty($files)) {
            foreach ($files as $name => $file) {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'goods');
                if ($info) {
                    $res = explode('_',$name);
                    $images[$res[1]][$res[2]] = $info->getSaveName();
                } else {
                    return array('message' => '上传图片失败');
                }
            }
        }

        //开始事务
        Db::startTrans();
        try {

            //添加评论记录
            foreach ($comment as $k => $v) {
                $data = array(
                    'memid' => $member_id,
                    'goods_id' => $v['goods_id'],
                    'order_id' => $order['order_id'],
                    'spec_name' => $order_goods[array_search($v['goods_id'], $order_goods_ids)]['spec_name'],
                    'content' => empty($v['content']) ? "不错哦": $v['content'],
                    'create_time' => TIMESTAMP,
                    'images' => empty($images) ? serialize(array()) : serialize($images[$v['goods_id']]),

                );
                if (!$model_comment->add($data)) {
                    throw new \Exception('添加评价失败');
                }


            }

            //修改订单状态
            if (!$this->model_order->where(['order_id' => $order['order_id']])->save(['iscomment' => ORDER_IS_COMMENT_YES])) {
                throw new \Exception('修改评论状态失败');
            }


        } catch (\Exception $e) {
            Db::rollback();
            return array('message' => $e->getMessage());
        }
        Db::commit();
        return array();

    }

    /**
     * 订单评价
     */
    public function order_evaluate()
    {


    }


    /**
     * 获取快递列表
     * @return array
     */
    public function expressList(){
        $express = array(
            ['code' => "shunfeng", 'name'=>"顺丰"],
            ['code' => "shentong", 'name'=>"申通"],
            ['code' => "yunda", 'name'=>"韵达快运"],
            ['code' => "tiantian", 'name'=>"天天快递"],
            ['code' => "yuantong", 'name'=>"圆通速递"],
            ['code' => "zhongtong", 'name'=>"中通速递"],
            ['code' => "ems", 'name'=>"ems快递"],
            ['code' => "huitongkuaidi", 'name'=>"百世汇通"],
            ['code' => "quanfengkuaidi", 'name'=>"全峰快递"],
            ['code' => "zhaijisong", 'name'=>"宅急送"],
            ['code' => "aae", 'name'=>"aae全球专递"],
            ['code' => "anjie", 'name'=>"安捷快递"],
            ['code' => "anxindakuaixi", 'name'=>"安信达快递"],
            ['code' => "biaojikuaidi", 'name'=>"彪记快递"],
            ['code' => "bht", 'name'=>"bht"],
            ['code' => "baifudongfang", 'name'=>"百福东方国际物流"],
            ['code' => "coe", 'name'=>"中国东方（COE）"],
            ['code' => "changyuwuliu", 'name'=>"长宇物流"],
            ['code' => "datianwuliu", 'name'=>"大田物流"],
            ['code' => "debangwuliu", 'name'=>"德邦物流"],
            ['code' => "dhl", 'name'=>"dhl"],
            ['code' => "dpex", 'name'=>"dpex"],
            ['code' => "dsukuaidi", 'name'=>"d速快递"],
            ['code' => "disifang", 'name'=>"递四方"],
            ['code' => "fedex", 'name'=>"fedex（国外）"],
            ['code' => "feikangda", 'name'=>"飞康达物流"],
            ['code' => "fenghuangkuaidi", 'name'=>"凤凰快递"],
            ['code' => "feikuaida", 'name'=>"飞快达"],
            ['code' => "guotongkuaidi", 'name'=>"国通快递"],
            ['code' => "ganzhongnengda", 'name'=>"港中能达物流"],
            ['code' => "guangdongyouzhengwuliu", 'name'=>"广东邮政物流"],
            ['code' => "gongsuda", 'name'=>"共速达"],
            ['code' => "hengluwuliu", 'name'=>"恒路物流"],
            ['code' => "huaxialongwuliu", 'name'=>"华夏龙物流"],
            ['code' => "haihongwangsong", 'name'=>"海红"],
            ['code' => "haiwaihuanqiu", 'name'=>"海外环球"],
            ['code' => "jiayiwuliu", 'name'=>"佳怡物流"],
            ['code' => "jinguangsudikuaijian", 'name'=>"京广速递"],
            ['code' => "jixianda", 'name'=>"急先达"],
            ['code' => "jjwl", 'name'=>"佳吉物流"],
            ['code' => "jymwl", 'name'=>"加运美物流"],
            ['code' => "jindawuliu", 'name'=>"金大物流"],
            ['code' => "jialidatong", 'name'=>"嘉里大通"],
            ['code' => "jykd", 'name'=>"晋越快递"],
            ['code' => "kuaijiesudi", 'name'=>"快捷速递"],
            ['code' => "lianb", 'name'=>"联邦快递（国内）"],
            ['code' => "lianhaowuliu", 'name'=>"联昊通物流"],
            ['code' => "longbanwuliu", 'name'=>"龙邦物流"],
            ['code' => "lijisong", 'name'=>"立即送"],
            ['code' => "q89985385q", 'name'=>"扫地神僧"],
            ['code' => "minghangkuaidi", 'name'=>"民航快递"],
            ['code' => "meiguokuaidi", 'name'=>"美国快递"],
            ['code' => "menduimen", 'name'=>"门对门"],
            ['code' => "ocs", 'name'=>"OCS"],
            ['code' => "peisihuoyunkuaidi", 'name'=>"配思货运"],
            ['code' => "quanchenkuaidi", 'name'=>"全晨快递"],
            ['code' => "quanjitong", 'name'=>"全际通物流"],
            ['code' => "quanritongkuaidi", 'name'=>"全日通快递"],
            ['code' => "quanyikuaidi", 'name'=>"全一快递"],
            ['code' => "rufengda", 'name'=>"如风达"],
            ['code' => "santaisudi", 'name'=>"三态速递"],
            ['code' => "shenghuiwuliu", 'name'=>"盛辉物流"],
            ['code' => "sue", 'name'=>"速尔物流"],
            ['code' => "shengfeng", 'name'=>"盛丰物流"],
            ['code' => "saiaodi", 'name'=>"赛澳递"],
            ['code' => "tiandihuayu", 'name'=>"天地华宇"],
            ['code' => "tnt", 'name'=>"tnt"],
            ['code' => "ups", 'name'=>"ups"],
            ['code' => "wanjiawuliu", 'name'=>"万家物流"],
            ['code' => "wenjiesudi", 'name'=>"文捷航空速递"],
            ['code' => "wuyuan", 'name'=>"伍圆"],
            ['code' => "wxwl", 'name'=>"万象物流"],
            ['code' => "xinbangwuliu", 'name'=>"新邦物流"],
            ['code' => "xinfengwuliu", 'name'=>"信丰物流"],
            ['code' => "yafengsudi", 'name'=>"亚风速递"],
            ['code' => "yibangwuliu", 'name'=>"一邦速递"],
            ['code' => "youshuwuliu", 'name'=>"优速物流"],
            ['code' => "youzhengguonei", 'name'=>"邮政包裹挂号信"],
            ['code' => "youzhengguoji", 'name'=>"邮政国际包裹挂号信"],
            ['code' => "yuanchengwuliu", 'name'=>"远成物流"],
            ['code' => "yuanweifeng", 'name'=>"源伟丰快递"],
            ['code' => "yuanzhijiecheng", 'name'=>"元智捷诚快递"],
            ['code' => "yuntongkuaidi", 'name'=>"运通快递"],
            ['code' => "yuefengwuliu", 'name'=>"越丰物流"],
            ['code' => "yad", 'name'=>"源安达"],
            ['code' => "yinjiesudi", 'name'=>"银捷速递"],
            ['code' => "zhongtiekuaiyun", 'name'=>"中铁快运"],
            ['code' => "zhongyouwuliu", 'name'=>"中邮物流"],
            ['code' => "zhongxinda", 'name'=>"忠信达"],
            ['code' => "zhimakaimen", 'name'=>"芝麻开门"],
            ['code' => "annengwuliu", 'name'=>"安能物流"],
        );
        return $express;
    }

    /**
     * 获取退货地址
     */
    public function refundAddress(){
        $request = $this->request->requestData();
        $member_id = $request['memid'];
        $order_id = $request['order_id'];
        $order = $this->checkOrder($order_id,$member_id);
        if(isset($order['message'])) return $order;
        if(!($order['refundid'] > 0)) return array('message' => '该订单未发起退货');
        $model_refund = db('refund');
        $refund = $model_refund->where(['refund_id' => $order['refundid']])->find();
        if(!$refund) return array('message' => '不存在的退货申请');
        if($refund['type'] !== REFUND_TYPE_GOOD_BEANS) return array('message' => '退款申请类型错误');
        if($refund['status'] !== REFUND_STATUS_SENDING) return array('message' => '退款订单状态错误');
        if($order['shopid'] > 0){
            $store = db('seller')->field('returnaddress,returnname,returnmobile')->where(['selid' => $order['shopid']])->find();
            if(empty($store['returnaddress']) || empty($store['returnname']) || empty($store['returnmobile'])){
                return array('message' => '商户退货信息不全，请联系客服');
            }
            return array(
                'address' => $store['returnaddress'],
                'name'    => $store['returnname'],
                'mobile'  => $store['returnmobile']
            );
        }else{
            $base = db('base')->field('returnaddress,returnname,returnmobile')->find(1);
            if(empty($base['returnaddress']) || empty($base['returnname']) || empty($base['returnmobile'])){
                return array('message' => '商户退货信息不全，请联系客服');
            }
            return array(
                array(
                    'address' => $base['returnaddress'],
                    'name'    => $base['returnname'],
                    'mobile'  => $base['returnmobile']
                )
            );
        }
    }
    /**
     * 申请中
     */
    public function refunding(){
        $request = $this->request->requestData();
        $condition['memid']=input('memid');
        if(!$condition['memid']) $condition['memid'] = $request['memid'];
        if(!$condition['memid']) return array('message'=>'memid有误');
        $condition['refund_id'] = input('refundid');
        if(!$condition['refund_id']) $condition['refund_id'] = $request['refundid'];
        if(!$condition['refund_id']) return array('message'=>'参数有误');
        $refund = db('refund')->where($condition)->find();
        if(!$refund) return array('message'=>'退款记录不存在');
        //物理公司
        $expressList=$this->expressList();
        $where['order_id']=$refund['order_id'];
        $order = db('order')->field('shopid,status,price')->where($where)->find();
        if($refund['type']==2){
            //退货地址
            if($order['shopid'] > 0){
                $store = db('seller')->field('returnaddress,returnname,returnmobile')->where(['selid' => $order['shopid']])->find();
                if(empty($store['returnaddress']) || empty($store['returnname']) || empty($store['returnmobile'])){
                    return array('message' => '商户退货信息不全，请联系客服');
                }
                $address= array(
                    'address' => $store['returnaddress'],
                    'name'    => $store['returnname'],
                    'mobile'  => $store['returnmobile']
                );
            }else{
                $base = db('base')->field('returnaddress,returnname,returnmobile')->find(1);
                if(empty($base['returnaddress']) || empty($base['returnname']) || empty($base['returnmobile'])){
                    return array('message' => '平台退货信息不全，请联系客服');
                }
                $address=array(
                    'address' => $base['returnaddress'],
                    'name'    => $base['returnname'],
                    'mobile'  => $base['returnmobile']
                );
            }
        }else{
            $address=(object)array();
        }
        return array('refund'=>$refund,'order'=>$order,'address'=>$address,);
    }
    /**
     * 退货信息
     */
    public function refundInfo(){
        $request = $this->request->requestData();
        $member_id = $request['memid'];
        $order_id = $request['order_id'];
        $order = $this->checkOrder($order_id,$member_id);
        if(isset($order['message'])) return $order;
        if(!($order['refundid'] > 0)) return array('message' => '该订单未发起退货');
        $model_refund = db('refund');
        $refund = $model_refund->where(['refund_id' => $order['refundid']])->find();
        if(!$refund) return array('message' => '不存在的退货申请');
        if($refund['type'] !== REFUND_TYPE_GOOD_BEANS) return array('message' => '退款申请类型错误');
        if($refund['status'] !== REFUND_STATUS_SENDING) return array('message' => '退款订单状态错误');
        // if(!empty($refund['express']) || !empty($refund['express_sn']) || !empty($refund['express_code'])) return array('message' => '该订单已提交退货信息');
        $express_sn = $request['express_sn'];
        if(!isset($express_sn) || empty($express_sn)) return array('message' => '物流单号不能为空');
        $express_code = $request['express_code'];
        if(!isset($express_code) || empty($express_code)) return array('message' => '物流代码不能为空');
        $express_list = $this->expressList();
        $code_list = array_column($express_list,'code');
        if(!($key = array_search($express_code,$code_list))) return array('message' => '不支持该快递物流公司');
        $express = $express_list[$key]['name'];
        if(!$model_refund->where(['refund_id' => $order['refundid']])->save(['express' => $express,'express_sn' => $express_sn,'express_code' => $express_code])){
            return array('message' => '录入退货信息失败');
        }
        return array();

    }


























}