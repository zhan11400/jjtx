<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/11/9  19:27
 */
namespace app\api\logic;
use app\api\BaseModel;
use app\api\controller\Wechat;
class Index extends  BaseModel{

    /**
     * 分类
     */
    public function category(){
        $category= db('goods_category');
        $res = $category ->field('id,name,image')->where("status=1 and pid=0")->order("sort desc")->limit(7)->select();
        foreach($res as &$v){
            $v['image']=IMG_PATH.$v['image'];
        }
        return $res;
    }
    public function index(){
	}
    /**
     * 轮播图
     */
    public function banner(){
        $banner = db('banner');
        $ban = $banner ->where("status=1 and type=0")->select();
        foreach($ban as &$v){
            $v['pic']=IMG_PATH.$v['pic'];
        }
        return $ban;
    }
    /**
     * 精品推荐
     */
    public function recommend(){
        $banner = db('banner');
        $ban = $banner ->field('id,pic,url') ->where("status=1 and type=1")->select();
        foreach($ban as &$v){
            $v['pic']=IMG_PATH.$v['pic'];
        }
        return $ban;
    }
    /**
     * 热门品牌
     */
    public function brand(){
        $brand = db('goods_brand');
        $data = $brand ->field('id,image,name') ->where("status=1")->limit(8)->select();
        foreach($data as &$v){
            $v['image']=IMG_PATH.$v['image'];
        }
        return $data;
    }
    /**
     * 今日爆款
     */
    public function home()
    {
        $userStatus = $this->checkUserStatus();
        $type = isset($this->requestData['type'])?$this->requestData['type']:'0';
        $where['g.status']=1;
        $where['g.is_home']=1;
        $p=isset($this->requestData['page'])?$this->requestData['page']:'1';
        $size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
        if($type==1){
            $list=db("goods")->alias("g")
                ->field("g.id,g.name,g.image,g.sales_num,g.des,min(p.price) as price,min(p.oldprice) as oldprice,min(p.agentprice) as agentprice")
                ->join("goods_option_value p","p.goodsid=g.id")
                ->where($where)
                ->group("g.id")
                ->order("g.sort desc,g.id desc")->page($p,$size)->select();
            $data['count']=db("goods")->alias("g")
                ->field("g.id,g.name,g.image,g.sales_num,g.des,min(p.price) as price,min(p.oldprice) as oldprice")
                ->join("goods_option_value p","p.goodsid=g.id")
                ->where($where)
                ->group("g.id")
                ->order("g.sort desc,g.id desc")->page($p,$size)->count();
        }else {
            $list = db("goods")->alias("g")
                ->field("g.id,g.name,g.image,g.sales_num,g.des,min(p.price) as price,min(p.oldprice) as oldprice,min(p.agentprice) as agentprice")
                ->join("goods_option_value p", "p.goodsid=g.id")
                ->where($where)
                ->group("g.id")
                ->order("g.sort desc,g.id desc")->limit(6)->select();
            $data['count']=6;
        }
        foreach($list as &$v){
            $v['image']=IMG_PATH. $v['image'];
            if($userStatus['type']==0){
                $v['price']='登录可见';
            }else{
                    $mao= $this->getmao($v['id']);
                    $v['price'] = floor($v['price'] * $userStatus['percent'] *$mao* 100) / 100;//当前价格
            }
        }
        $data['list']=$list;
        $data['page_num']=$p;
        $data['page_limit']=$size;
        return $data;
    }
    /**
     * 注册协议，关于我们
     */
    public function contract(){
        $id=input('id');
        $brand = db('contract');
        $data = $brand ->where("id='$id'")->find();
        $data['content']=htmlspecialchars_decode($data['content']);
        return $data;
    }
    /**
     * 经销商列表
     */
    public function agent(){
        $county=input('county');
        $agent = db('agent');
        $data = $agent ->field("id,name")->where("status=1 and county='$county'")->select();
        return $data;
    }
    /**
     * 联系我们
     */
    public function contact(){
        $agent = db('config');
        $data = $agent ->field("id,value")->where("id<3")->select();
        return $data;
    }

    public function getagent()
    {
        if(isset($this->requestData['access_token']) && !empty($this->requestData['access_token'])) {
            $Oauth2 = new \app\api\service\Oauth2();
            $Oauth = ($Oauth2->getUserInfoByToken($this->requestData['access_token']));
            $member = checkMember($Oauth['user']['id']);
            if ($member['agent']) {
                $agent = db("agent")->field("name,percentage")->where('id=' . $member['agent'])->find();
                $percent = $agent['percentage'] / 100;
            }else{
                $agent=array('name'=>'总平台','percent'=>1);
                $percent=1;
            }
        }else{
            $agent=array('name'=>'总平台','percent'=>1);
            $percent=1;
        }
        return array("name"=>$agent['name'],"percent"=>$percent);
    }
}