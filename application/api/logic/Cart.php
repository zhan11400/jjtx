<?php
/**
 * Created by PhpStorm.
 * member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\logic;
use app\api\BaseModel;
use think\Model;
use think\Db;

class Cart extends BaseModel
{
    //添加购物车
    public function cart_add()
    {
        $total=isset($this->requestData['total'])?$this->requestData['total']:1;
        $condition['g.status']=1;
        $condition['p.id']=$this->requestData['specid'];
        $goods=db('goods')->alias("g")
            ->field("p.id,p.goodsid,p.stock")
            ->join("goods_option_value p","p.goodsid=g.id")
            ->where($condition)
            ->find();
        if(!$goods)  return array('message'=>'商品不存在');
        if($goods['stock']<$total)  return array('message'=>'规格库存不足'.$total);
        $card['uid']=$this->user['id'];
        $card['goodsid']=$goods['goodsid'];
        $card['specid']=$goods['id'];
        $collect = db('cart')->where($card)->find();
        if($collect){
           $res= db('cart') ->where($card) ->setInc('total',$total);
        }else{
            $data['uid'] = $this->user['id'];;
            $data['specid'] = $goods['id'];
            $data['total'] = $total;
            $data['goodsid'] = $goods['goodsid'];
            $data['createtime'] =time();
            $res= db('cart')->insert($data);
        }
        if(!$res) return array('添加失败');
    }
    // 勾选购物车中的商品
    public function cart_select()
    {
        $id =$this->requestData['id'];
        $where['uid']=$this->user['id'];
        $where['id']=$id;
            $cart=db('cart')->where("id='$id'")->find();
            if (empty($cart)) return array('message'=>'商品不存在');
                if($cart['isselect']==1){
                    $save['isselect']=0;
                }else{
                    $save['isselect']=1;
                }
        $res=db('cart')->where($where)->update($save);
        if($res!==false) return array('status'=> $save['isselect']);
        return array('message'=>'更新失败');
    }
    /*
     * 更新购物车里面商品数量
     */
    public function cart_update()
    {
        $id =$this->requestData['id'];
        $goodstotal  =$this->requestData['count'];
        $where['c.uid']=$this->user['id'];
        $where['c.id']=$id;
        $goods=db('cart')->alias('c')
            ->join("goods_option_value p","p.id=c.specid")
           ->join("goods g","g.id=c.goodsid")
            ->where($where)
            ->find();
        if (empty($goods))    return array('message'=>'商品未找到');
        if($goods['stock']<$goodstotal) return array('message'=>'总库存为'.$goods['stock']);
        $cart['total']=$goodstotal;
        $res=db('cart')->where("id='$id'")->update($cart);
        if($res!==false) return array();
        return array('message'=>'更新失败');
    }
    /*
     * 购物车列表
     */
    public function cart_list()
    {
        $memid=$this->user['id'];
        $UserStatus=$this->checkUserStatus();
        $list=db('cart')->alias('c')
            ->field('c.id,c.total,c.isselect,g.name,g.status,g.image,p.spec_name,p.price,p.agentprice,c.goodsid')
        ->join('goods g','g.id=c.goodsid')
        ->join('goods_option_value p',"p.id=c.specid")
        ->where("uid='$memid'")->select();
        foreach($list as &$v){
			 $mao= $this->getmao($v['goodsid']);
            $v['price']=floatval($v['price'])*$UserStatus['percent']*$mao;
            $v['image']=IMG_PATH. $v['image'];
        }
        return $list;
    }
    //移除购物车
    public function cart_remove()
    {
        $where['uid']=$this->user['id'];
        $where['id']=$this->requestData['id'];
        $data=db('cart')->where($where)->find();
        if(!$data) return array('message'=>'不存在');
        $res=db('cart')->where($where)->delete();
        if(!$res)return array('message'=>'删除失败');
        return array();
    }
    public  function checkMember($member_id){
        if($member_id < 1) return array('message'=>'memid有误');
        $where = array('memid' => $member_id);
        $member = db('member')->where($where)->find();
        if(empty($member)) return array('message'=>'会员不存在');
        return $member;
    }
}
