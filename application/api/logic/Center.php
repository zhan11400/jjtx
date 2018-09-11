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
class Center extends \app\api\BaseModel
{
	
	public function __construct(){
		parent::__construct();
		$this->member   = db('member');
	}
	
    /*
     *	修改个人资料
     */
	public function modify(){
    	
		$id   = $this->user['id'];
		//省
		if(isset($this->requestData['province'])){
			$province = trim($this->requestData['province']);
			$data['province'] = $province;
		}
		
		//市
		if(isset($this->requestData['city'])){
			$city = trim($this->requestData['city']);
			$data['city'] = $city;
		}
		
		//区
		if(isset($this->requestData['county'])){
			$county = trim($this->requestData['county']);
			$data['county'] = $county;
		}
		
		$update = $this->member->where("id='$id'")->update($data);
		if(!$update){
			return array('message'=>'修改失败');	
		}else{
			return array();	
		}
    }
	/*
     *	绑定手机
     */
	public function bind(){
		$id   = $this->user['id'];
		//手机号
		if(isset($this->requestData['mobile'])){
			if(isset($this->requestData['code'])){
				$code = $this->code($this->requestData['code']);
				if($code==1) return array('message'=>'验证码错误');
				if($code==2){
					$mobile = trim($this->requestData['mobile']);
					$find = $this->member->where("mobile='$mobile'")->find();
					if($find) return array('message'=>'该手机已被绑定');
					$data['mobile'] = $mobile;
					$update = $this->member->where("id='$id'")->update($data);
					if(!$update){
						return array('message'=>'绑定失败');	
					}else{
						return array();	
					}
				}else{
					return array('message'=>'绑定失败');	
				} 
			}else{
				return array('message'=>'验证码为空');
			}
		}else{
			return array('message'=>'手机号为空');
		}
    }
	/**
     * 提醒记录
     */
    public function news_log(){
		$p=isset($this->requestData['page'])?$this->requestData['page']:'1';
		$size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
		$where['uid']=$this->user['id'];
		$list=db("news_log")
				->where($where)
				->order("id desc")
				->page($p,$size)->select();
		foreach($list as &$v){
			$v['create_time']=date("Y-m-d H:i",$v['create_time']);
		}
		$count=db("news_log")
				->where($where)
				->count();
		return array("list"=>$list,"page_num"=>$p,"page_limit"=>$size,"count"=>$count);
    }
	/*
     *	会员中心
     */
	public function center(){
		$member=db("member")->where("id=".$this->user['id'])->find();
		unset($member['passwd']);
		$isagent=$this->checkagent($member['id']);
		if($isagent==1){
			$member['isagent']=1;
		}else{
			$member['isagent']=0;
		}
		return $member;
    }

	/*
 *	信用额
 */
	public function credit()
	{
		$member=db("member")->where("id=".$this->user['id'])->find();
		$used=db("order")->where("pay_type=1 and status >0 and uid=".$this->user['id'])->sum("price");
		return array("credit"=>$member['credit']+$used,'available'=>$member['credit'],'used'=>$used);
	}
	/**
 *	信用额记录
 */
	public function credit_log(){
		$used=db("order")->field("id,order_sn,price,create_time")
				->where("pay_type=1 and(status ='1' or status='2') and uid=".$this->user['id'])
				->order("id desc")
				->select();
		foreach($used as &$v){
			$v['create_time']=date("Y-m-d",$v['create_time']);
		}
		return $used;
	}
	/*
 *	系统信息设置已读
 */
	public function read(){
		$where['uid']= $this->user['id'];
		$where['id']= $this->requestData['id'];
		$news=db("news_log")->where($where)->find();
		if(!$news) return array('message'=>'记录不存在');
		$res=db("news_log")->where($where)->update(array("status"=>1));
		return array();
	}
	/*
*	信息删除
*/
	public function delete(){
		$where['uid']= $this->user['id'];
		$where['id']= $this->requestData['id'];
		$news=db("news_log")->where($where)->find();
		if(!$news) return array('message'=>'记录不存在');
		$res=db("news_log")->where($where)->delete();
		if(!$res) return array('message'=>'删除失败');
		return array();
	}
}

?>