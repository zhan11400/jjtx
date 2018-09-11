<?php
/**
 * Created by PhpStorm.
 * member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\logic;
use app\api\BaseModel;
use app\api\model\Shop;
use think\Model;
use think\Request;
use think\Db;
class Collect extends BaseModel
{
	/**
	 * 商品收藏与取消
	 */
	public function collect_goods()
	{
		$condition['id']=$this->requestData['id'];
		$goods=db("goods")->where($condition)->find();
		if(!$goods) return array('message'=>'商品不存在');
		$where['uid']=$this->user['id'];
		$where['goodsid']=$this->requestData['id'];
		$collect=db('collect_goods')->where($where)->find();
		if(!$collect){
			$res=db('collect_goods')->insert($where);
			$result['status']=1;
		}else{
			$res=db('collect_goods')->where($where)->delete();
			$result['status']=0;
		}
		return $result;
	}

	/**
	 * 收藏商品列表
	 */
	public function collect_goods_list()
	{
		$collect_goods=db('collect_goods');
		$goods_option_value=db('goods_option_value');
		$p=isset($this->requestData['page'])?$this->requestData['page']:'1';
		$size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
		$where['c.uid']=$this->user['id'];
		$data['count']=$collect_goods->alias('c')->join('goods g','c.goodsid=g.id')->where($where)->count();
		$data['list']=$collect_goods->alias('c')
				->field('c.id,g.name,g.image,c.goodsid')
				->join('goods g','c.goodsid=g.id')
				->where($where)->page($p,$size)->select();

		foreach($data['list'] as $k=>$v){
			$data['list'][$k]['price']=(float)($goods_option_value->where("goodsid=".$v['goodsid'])->min("price"));
			$data['list'][$k]['image']=IMG_PATH.$v['image'];
		}
		$data['page_num']=$p;
		$data['page_limit']=$size;
		return $data;
	}
	/**
	 * 删除收藏商品，单个或多个删除
	 */
	public function collect_goods_remove()
	{
		$where['uid']=$this->user['id'];
		$where['id']=array("in",$this->requestData['ids']);
		$res=db('collect_goods')->where($where)->delete();
		if(!$res) return array('系统繁忙，稍后再试');
	}
}
