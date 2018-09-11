<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/9/30 16:12
 */

namespace app\api\logic;


use app\api\BaseModel;
use think\Db;

class Coupon extends BaseModel
{
    public function getCouponByUser()
    {

    }

    /**
     * 店铺抵扣劵列表
     */
    public function index()
    {
        $uid=$this->user['id'];
        $list = db('coupon')->where(['status' => 1,'delete' => 0])->select();
        $list2=array_column($list,'id');
        $mycoupon= db('mycoupon')->where(['user_id' => $uid])->select();
        $list3=array_column($mycoupon,'voucher_id');
        $list4=array_intersect($list2,$list3);
        if(!empty($list4)){
            foreach($list4 as $l){
                $key = array_search($l,$list2);
                unset($list[$key]);
            }
        }
        $list=array_merge($list);
       foreach($list as &$v){
            $v['image']=IMG_PATH.$v['image'];
        }
        return $list;
    }
    /**
     * 领取抵扣券
     */
    public function receive()
    {
        $uid=$this->user['id'];
        $coupon = db('coupon')->where(['id' => intval($this->requestData['id'])])->find();
        if (!$coupon) return array('message' => '该优惠劵不存在');
        if ($coupon['status'] != 1) return array('message' => '该优惠劵已抢完');
        if ($coupon['count'] < 1) return array('message' => '该优惠劵已抢光');
        $mycoupon = db('mycoupon')->where(['voucher_id' => intval($this->requestData['id']),'user_id' =>$uid])->find();
        if($mycoupon) return array('message' => '你已领取过了！');
        $data = array(
            'user_id' => $uid,
            'voucher_id'=>$coupon['id'],
            'name' => $coupon['name'],
            'money' => $coupon['deduction'],
            'fullmoney' => $coupon['fullmoney'],
            'start_time' => TIMESTAMP,
            'end_time' => TIMESTAMP+$coupon['useful_day']*24*60*60
        );

        Db::startTrans();
        try {
            //减少优惠券数量
            if (!db("coupon")->where(['id' => intval($this->requestData['id'])])->setDec('count')) throw new \Exception('减少优惠券数量');
            //生成优惠劵
            if (!db('mycoupon')->insert($data)) throw new \Exception('生成优惠劵失败');
            //增加优惠劵销量
            if (!db("coupon")->where(['id' => intval($this->requestData['id'])])->setInc('sales_num')) throw new \Exception('增加销量失败');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return array('message' => $e->getMessage());
        }
    }

    /**
     * 我的抵扣劵列表
     */
    public function mycoupon()
    {
        $uid=$this->user['id'];
        $status=input('status');
        if($status && !in_array($status,array(1,-1))) return array("message"=>'参数有误');
        $p=isset($this->requestData['page'])?$this->requestData['page']:'1';
        $size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
        $where['user_id']=$uid;
        $where['status']=0;
        $list = db('mycoupon')->where($where)->page($p,$size)->select();
        $count= db('mycoupon')->where($where)->count();
        foreach($list as &$v){
            if($v['status']==0){
                if($v['end_time']<TIMESTAMP){
                    db('mycoupon')->where(['user_id' =>$uid,'id'=>$v['id']])->update(array('status'=>-1));
                    $v['status']=-1;
                }
            }
            $v['start_time']=date("Y-m-d H:i",$v['start_time']);
            $v['end_time']=date("Y-m-d H:i",$v['end_time']);
            $eee = db('coupon')->where(['id' =>$v['voucher_id'],'delete' => 0])->find();
            $v['image']=IMG_PATH.$eee['image'];

        }
        return array('list' => $list,'page_num'=>$p,'page_limit'=>$size,'count'=>$count);
    }

}