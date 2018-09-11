<?php
namespace app\api\logic;
use app\api\BaseModel;
use think\Cache;
use think\Request;
/**
 * Class descript.
 * User: chen guang
 * Date: 2017/11/9 11:22
 */
class Agent extends \app\api\BaseModel
{
	
	public function __construct(){
		parent::__construct();
		$this->member   = db('member');
	}

	/*
       *	经销商中心
       */
	public function center(){
		$uid= $this->user['id'];
		$agent=$this->member->alias("m")
				->field("a.province,a.city,a.county,a.name,m.headimage")
			->join("agent a","m.id=a.uid")
			->where("a.status=1 and uid=".$uid)
			->find();
		return $agent;
	}
	/**
	 * 	门吊
     */
	public function shop(){
		$condition['agent']= $this->user['id'];
		if (isset($this->requestData['status']) && $this->requestData['status']!='') {//
			$condition['status'] =$this->requestData['status'];
		}
		$p=isset($this->requestData['page'])?$this->requestData['page']:'1';
		$size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
		$agent=$this->member
				->field("id,company,mobile,headimage,province,city,county,status,type")
				->where($condition)
				->page($p,$size)
				->select();
		$count=$this->member
				->field("id,company,mobile,headimage,province,city,county,status,type")
				->where($condition)
				->count();
		return array('list' => $agent,'page_num'=>$p,'page_limit'=>$size,'count'=>$count);
	}
	/**
	 * 订单列表
	 **/
	public function order_list()
	{
		 $memid = $this->user['id'];
		if (isset($this->requestData['status'])) {//全部订单
			$condition['status'] =$this->requestData['status'];
		} else {
			$condition['status'] = array('gt', -2);
		}
		$condition['agentid'] = $memid;
		$condition['isdeleted'] = 0;
		$p = isset($this->requestData['page']) ? $this->requestData['page'] : '1';
		$size = isset($this->requestData['limit']) ? $this->requestData['limit'] : '10';
		$list = db('order')->field('id,refundid,pay_time,order_sn,status,isremind,pay_type,send_type,send_fee')
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
				$v['goods'][$k]['goods_image'] = getImageUrl('goods', $item['goods_image']);
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
	/*
       *	审核注册会员
       */
	public function deal(){
		$id= $this->requestData['id'];
		$status = isset($this->requestData['status']) ? $this->requestData['status'] : -1;
		$type= isset($this->requestData['type']) ? $this->requestData['type'] : 1;
		if(!in_array($status,array(-1,1))) return array("状态有误");
		$uid= $this->user['id'];
		$member=$this->member
				->where("agent=".$uid." and id=".$id)
				->find();
		if(!$member) return array("申请记录不存在");
		if($status==1){
			if(!in_array($type,array(2,1))) return array("状态有误");
			$res=$this->member
					->where("agent=".$uid." and id=".$id)
					->update(array("status"=>$status,"type"=>$type));
		}else{
			$res=$this->member
					->where("agent=".$uid." and id=".$id)
					->update(array("status"=>$status,"wx_openid"=>$type));
		}
		if($res===false) return array("失败");
		return array();
	}
	//查看订单详情
	public function order_detail()
	{
		$condition['agentid'] = $this->user['id'];
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

}

?>