<?php

/**
 * 首页
 * User: tsang
 * Date: 2017/9/14
 * Time: 20:11
 */

namespace app\index\Controller;
use Doctrine\Common\Cache\MemcacheCache;
use think\Session;
use think\cache\driver\Redis;
class Index extends Common
{

    /*
    *	构造函数
    */
    function __construct(){
        parent::__construct();
    }

    /*
    *	后台主界面
    */
    public function index(){
        //注册的会员数量
        $member_db=db("member");
        $member['today']=$member_db->where('create_time','between',[strtotime(date("Y-m-d")),time()])->count();
        $member['yesterday']=$member_db->where('create_time','between',[strtotime(date("Y-m-d"))-24*60*60,strtotime(date("Y-m-d"))])->count();
        $member['week']=$member_db->where('create_time','between',[strtotime(date("Y-m-d"))-6*24*60*60,time()])->count();
        $member['month']=$member_db->where('create_time','between',[strtotime(date("Y-m-d"))-29*24*60*60,time()])->count();
        $member['all']=$member_db->where('create_time','gt',0)->count();
        //订单数量
        $order_db=db("order");
        $today['pay_time']=array('between',[strtotime(date("Y-m-d")),time()]);
        $today['status']=array('gt',0);
        $order['today']=$order_db->where($today)->count();
        $yesterday['pay_time']=array('between',[strtotime(date("Y-m-d"))-24*60*60,strtotime(date("Y-m-d"))]);
        $yesterday['status']=array('gt',0);
        $order['yesterday']=$order_db->where($yesterday)->count();
        $week['pay_time']=array('between',[strtotime(date("Y-m-d"))-6*24*60*60,time()]);
        $week['status']=array('gt',0);
        $order['week']=$order_db->where($week)->count();
        $month['pay_time']=array('between',[strtotime(date("Y-m-d"))-29*24*60*60,time()]);
        $month['status']=array('gt',0);
        $order['month']=$order_db->where($month)->count();
        $all['status']=array("gt",0);
        $order['all']=$order_db->where($all)->count();

        $a=$this->get_week(time()-10*60*60*24);
        $str='[';
        $num='[';
        $user='[';
        for($i=0;$i<count($a);$i++){
            if($i==0){
                $str.=   "'".$a[$i]['date']."'";
                $num.=   $a[$i]['num'];
                $user.=   $a[$i]['user'];
            }else{
                $str.=",". "'".$a[$i]['date']."'";
                $num.=",".$a[$i]['num'];
                $user.=",".$a[$i]['user'];
            }

        }
        $str.=']';
        $num.=']';
        $user.=']';
        $this->assign("date",$str);
        $this->assign("num",$num);
        $this->assign("user",$user);
        $this->assign("order",$order);
        $this->assign("member",$member);
        return $this->fetch();
    }
    public function get_week($time, $format = "m-d") {
        $order_db=db("order");
        $member_db=db("member");
        for ($i=0;$i<=9;$i++){
            $data[$i]['date'] = date($format,strtotime( '+'.$i+1 .' days',$time));
            $where['pay_time']=array("between",[strtotime(date('Y-m-d',strtotime( '+'.$i+1 .' days',$time))),strtotime(date('Y-m-d',strtotime( '+'.$i+1 .' days',$time)))+24*60*60]);
            $data[$i]['num']=$order_db->where($where)->count();
            $ddd['create_time']=array("between",[strtotime(date('Y-m-d',strtotime( '+'.$i+1 .' days',$time))),strtotime(date('Y-m-d',strtotime( '+'.$i+1 .' days',$time)))+24*60*60]);
            $data[$i]['user']=$member_db->where($ddd)->count();
        }
        ksort($data);
        return $data;
    }


}