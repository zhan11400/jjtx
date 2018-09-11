<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/11/9  19:27
 */
namespace app\api\logic;
use app\api\BaseModel;
use think\Cache;

class Goods extends  BaseModel{


    /**
     * 分类列表
     */
    public function category(){
        $category= db('goods_category');
        $res = $category ->field('id,name,image')->where("status=1 and pid=0")->order("sort desc")->select();
        foreach($res as &$v){
            $v['image']=IMG_PATH.$v['image'];
            $v['child']=$category->field('id,name,image')->where("status=1 and pid=".$v['id'])->order("sort desc")->select();
            foreach($v['child'] as &$d){
                $d['image']=IMG_PATH.$d['image'];
            }
        }
        return $res;
    }
    /**
     * 热门品牌
     */
    public function brand(){
        $brand = db('goods_brand');
        $data = $brand ->field('id,image,name') ->where("status=1")->order("sort desc")->select();
        foreach($data as &$v){
            $v['image']=IMG_PATH.$v['image'];
        }
        return $data;
    }
    /**
     * 商品列表
     */
    public function index()
    {
        $userStatus = $this->checkUserStatus();
        $p=isset($this->requestData['page'])?$this->requestData['page']:'1';
        $size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
        $cid=isset($this->requestData['cid'])?$this->requestData['cid']:'0';
        $type=isset($this->requestData['type'])?$this->requestData['type']:'0';
        if(intval($cid)!=0){
            $where['g.cid']=$cid;
            $category=db("goods_category")->where("pid='$cid' and status=1")->select();
        }else{
            $category=0;
        }
        if(isset($this->requestData['did']) && !empty($this->requestData['did'])){
            $where['g.did']=$this->requestData['did'];
        }
        if(isset($this->requestData['nid']) && !empty($this->requestData['nid'])){
            $id=$this->requestData['nid'];
            $news_log=db('news_log')->where("id='$id'")->find();
            $goods=unserialize($news_log['goods']);
            $str=implode(",",$goods);
            $where['g.id']=array('in',$str);
        }
        if(isset($this->requestData['model']) && !empty($this->requestData['model'])){
            $where['g.modelstr']=array("like",'%'.$this->requestData['model'].'%');
        }
        if(isset($this->requestData['keyword']) && !empty($this->requestData['keyword'])){
                $where['g.name|g.vin'] = array("like", '%' . $this->requestData['keyword'] . '%');
            //热搜
            $hot_search_goods = Cache::get("hot_search_goods");
            $hot_search_goods = json_decode($hot_search_goods);
            $hot_search_goods[] = $this->requestData['keyword'];
            Cache::set("hot_search_goods", json_encode($hot_search_goods), 864000);
            //热搜
          //搜索历史
            if($userStatus['uid']>0){
                $history = Cache::get("history");
                //$history = Cache::rm("history");exit;
                $history = json_decode($history);
                $history[] = serialize(array("uid" => $userStatus['uid'], 'name' => $this->requestData['keyword']));
                Cache::set("history", json_encode($history), 864000);
            }
            //热搜

        }
        if(isset($this->requestData['brand']) && !empty($this->requestData['brand'])){
            $where['g.brand']=$this->requestData['brand'];
        }
        $where['g.status']=1;
        $list=db("goods")->alias("g")
            ->field("g.id,g.name,g.image,g.sales_num,g.des,min(p.price) as price,min(p.oldprice) as oldprice,min(p.agentprice) as agentprice")
            ->join("goods_option_value p","p.goodsid=g.id")
            ->where($where)
            ->group("g.id")
            ->order("g.sort desc,g.id desc")->page($p,$size)->select();
        foreach($list as &$v){
            $v['image']=IMG_PATH. $v['image'];
            if($userStatus['type']==0){
                $v['price']='登录可见';
            }else{
                     $mao= $this->getmao($v['id']);
                    $v['price'] = floor($v['price'] * $mao*$userStatus['percent'] * 100) / 100;//当前价格
            }
        }
        $data['list']=$list;
        $data['count']=db("goods")->alias('g')->join('goods_option_value p','p.goodsid=g.id')->where($where)->count();
        $data['page_num']=$p;
        $data['page_limit']=$size;
        $data['category']=$category;
        return $data;
    }

    /**
     * 热搜
     */
    public function hot_search(){
        $my_history=array();
        $userStatus = $this->checkUserStatus();
       $uid=$userStatus['uid'];
        if($userStatus['uid']>0) {
            ;            //我的搜索历史
            $history = Cache::get("history");
            $history = json_decode($history);
            $k = 0;
            if ($history) {
                $my_array = array_count_values($history);
                foreach ($my_array as $key => $value) {
                    $a = unserialize($key);
                    if ($a['uid'] == $uid) {
                        $my_history[] = $a['name'];
                    }
                    $k++;
                }
                $my_history = array_slice($my_history, 0, 6);
            }
        }

        $hot_new_array = array();
        //热搜
        $hot_search_goods = Cache::get("hot_search_goods");
        $hot_search_goods = json_decode($hot_search_goods);
        //将重复的元素值筛选出来
        if($hot_search_goods) {
            $hot_array = array_count_values($hot_search_goods);
            $i = 0;
            foreach ($hot_array as $key => $value) {
                $hot_new_array[$i]['search_name'] = $key;
                $hot_new_array[$i]['search_count'] = $value;
                $i++;
            }
            array_multisort(array_column($hot_new_array, 'search_count'), SORT_DESC, $hot_new_array);
        }
        $hot_new_array = array_slice($hot_new_array,0,5);

        return array("hot"=>$hot_new_array,"history"=>$my_history);
    }

    /**
     * 删除搜索记录
     */
    public function del_search(){
        $access_token = $this->requestData['access_token'];
        try{
            $Oauth2 = new \app\api\service\Oauth2();
            $Oauth=($Oauth2->getUserInfoByToken($access_token));
            //权限控制（群主有封禁用户服务能力）
        }catch (Exception $e){
            $returnData = json_encode([
                'data' => array(),
                'code' => -1,
                'message' => $e->getMessage(),
                'errorCode' => $e->errorCode
            ]);
            exit($returnData);
        }
        //我的搜索历史
        $history = Cache::get("history");
        $history = json_decode($history);
        $ddd=array();
        foreach($history as &$value){
            $a=unserialize($value);
            if($a['uid']!=$Oauth['user']['id']){
                $ddd[]=$value;
            }
        }
        Cache::set("history", json_encode($ddd), 864000);
        return array();
    }
    /**
     * 商品详情
     */
    public function detail(){
        $userStatus = $this->checkUserStatus();
        $goods = db('goods');
        $where['id']=$this->requestData['id'];
        $where['status']=1;
        $list=$goods->where($where)->find();
        if(!$list) return array('message'=>'商品不存在');
        if($list['image']) {
            $list['image']=IMG_PATH . $list['image'];
        }
        if($list['images']) {
            $list['images'] = unserialize($list['images']);
            foreach ($list['images'] as $k => $v) {
                $list['images'][$k] = IMG_PATH . $v;
            }
        }
        if($list['option']) {
            $list['option'] = unserialize($list['option']);
        }
        if($list['model']) {
            $list['model'] = unserialize($list['model']);
        }
        if($list['brand']) {
            $brand=db("goods_brand")->field("name,id")->where("id=".$list['brand'])->find();
            $list['brand'] =$brand['name'];
        }
        $db_brand=db("goods_model");
        if($list['model']) {
            foreach ($list['model'] as &$de) {
                $de['model1'] = $db_brand->field("name,id")->where("id=" . $de['model1'])->find();
                $de['model2'] = $db_brand->field("name,id")->where("id=" . $de['model2'])->find();
                $de['model3'] = $db_brand->field("name,id")->where("id=" . $de['model3'])->find();
                $de['model4'] = $db_brand->field("name,id")->where("id=" . $de['model4'])->find();
            }
        }
        $list['stock']=db("goods_option_value")->where("goodsid=".$this->requestData['id'])->sum("stock");
        if($userStatus['type']==0){
            $list['price']='登录可见';
        }else{
            $min=db("goods_option_value")->where("goodsid=".$this->requestData['id'])->min("price");
            $max=db("goods_option_value")->where("goodsid=".$this->requestData['id'])->max("price");
            $mao= $this->getmao($list['id']);
                $list['price']=$min* $userStatus['percent']*$mao.'-'.$max* $mao*$userStatus['percent'];//floor($v['price'] * $userStatus['percent'] * 100) / 100;//当前价格
        }
        $list['spec']=$this->getoption($this->requestData['id'],$userStatus['percent']);
        return $list;
    }
    public function getoption($id,$percent)
    {
        $where['goodsid'] = $id;
        $mao= $this->getmao($id);
        $option = db("goods_option_value")->field("id,agentprice,price,spec_name,stock")->where($where)->select();
        foreach($option as &$v) {
                $v['price'] = floatval($v['price'])*$percent*$mao;
        }
        return $option;
    }
    /*
  * 规格列表
  */
    public function spec()
    {
        $id = intval($this->requestData['id']);
        if (!$id) return array('message' => '参数有误');
        $where['goodsid'] = $id;
        $spec = db("goods_option_category");
        $item = db("goods_option");
        $spec = $spec->field("id,title")->where($where)->order("id asc")->select();
        foreach ($spec as &$v) {
            $v['list'] = $item->field("title,id")->where("option_category=".$v['id'])->select();
        }
        return $spec;
    }
    /*
 * 选择规格
 */
    public function option()
    {
        $id = intval($this->requestData['id']);
        if (!$id) return array('message' => '参数有误');
        $ids = $this->requestData['ids'];
        $ids=explode(",",$ids);
        asort($ids);
        $where['goodsid'] = $id;
        $where['specs'] = implode(",",$ids);
        $option = db("goods_option_value")->where($where)->find();
        if (!$option) array("message" => '记录不存在');
        $option['price'] = floatval($option['price']);
        return $option;
    }

    /**
     * 商品评论
     */
    public function comment(){
        $comment = db('comment');
        $p=isset($this->requestData['page'])?$this->requestData['page']:'1';
        $size=isset($this->requestData['limit'])?$this->requestData['limit']:'10';
        $where['c.goodsid']=$this->requestData['goodsid'];
        $where['c.status']=0;
        $list=$comment->alias("c")
            ->field("m.headimage,m.id,m.nickname,c.*")
            ->join("member m","m.id=c.uid")
            ->where($where)
            ->page($p,$size)
            ->order("create_time desc")->select();
        $count=$comment->alias("c")
            ->join("member m","m.id=c.uid")
            ->where($where)->count();
        foreach($list as &$v){
            if($v['images']){
                $images=unserialize($v['images']);
                foreach($images as &$d){
                    $d=IMG_PATH.$d;
                }
                $v['images']=$images;
            }else{
                $v['images']=array();
            }
            $v['create_time']=date("Y-m-d",$v['create_time']);
        }
        return array("list"=>$list,'page_num'=>$p,'page_limit'=>$size,'count'=>$count);
    }
    /**
     * 车型三四级列表
     */
    public function modelchild(){
        $where['pid']=input('id');
        if(empty($where['pid'])) return array('message'=>'参数有误');
        $where['status']=1;
        $list = db("goods_model")->field('id,pid,name')->where($where)->select();
        foreach ($list as &$v) {
            $arr['pid']=$v['id'];
            $arr['status']=1;
            $v['child']=db("goods_model")->field('id,name')->where($arr)->order("id desc")->select();
        }
        return $list;
    }
    /**
     * 车型列表
     */
    public function model(){

        $where['pid']=0;
        $where['status']=1;
        $list = db("goods_model")->where($where)->select();
        foreach ($list as &$v) {
            $arr['pid']=$v['id'];
            $arr['status']=1;
            $v['image']=IMG_PATH.$v['image'];
            $v['child']=db("goods_model")->field('id,name,pid')->where($arr)->order("id desc")->select();
            foreach($v['child'] as $k=>$d){
                $add['pid']=$d['id'];
                $add['status']=1;
                $v['child'][$k]['childnum']=db("goods_model")->field('id,name')->where($add)->order("id desc")->count();
            }
        }
      return $this->groupByInitials($list,'name');
    }
    /**
     * 二维数组根据首字母分组排序
     * @param  array  $data      二维数组
     * @param  string $targetKey 首字母的键名
     * @return array             根据首字母关联的二维数组
     */
    public function groupByInitials(array $data, $targetKey = 'name')
    {
        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'initials' => $this->getInitials($item[$targetKey]),
            ]);
        }, $data);
        $data = $this->sortInitials($data);
        return $data;
    }
    /**
     * 按字母排序
     * @param  array  $data
     * @return array
     */
    public function sortInitials(array $data)
    {
        $sortData = [];
        $arr=array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        foreach ($data as $key => $value) {
            $sortData[$value['initials']][] = $value;
        }
        ksort($sortData);
        foreach ($sortData as $key => $value) {
            foreach ($arr as $k => $v) {
                if ($v == $key) {
                   $eee[]=array("key"=>$key,"list"=>$value);
                }
            }
        }
        return $eee;
    }

    public function getInitials($str){
        if(empty($str)){return '';}
        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
        $s1=iconv('UTF-8','gb2312',$str);
        $s2=iconv('gb2312','UTF-8',$s1);
        $s=$s2==$str?$s1:$str;
        $asc=ord($s{0})*256+ord($s{1})-65536;
        if($asc>=-20319&&$asc<=-20284) return 'A';
        if($asc>=-20283&&$asc<=-19776) return 'B';
        if($asc>=-19775&&$asc<=-19219) return 'C';
        if($asc>=-19218&&$asc<=-18711) return 'D';
        if($asc>=-18710&&$asc<=-18527) return 'E';
        if($asc>=-18526&&$asc<=-18240) return 'F';
        if($asc>=-18239&&$asc<=-17923) return 'G';
        if($asc>=-17922&&$asc<=-17418) return 'H';
        if($asc>=-17417&&$asc<=-16475) return 'J';
        if($asc>=-16474&&$asc<=-16213) return 'K';
        if($asc>=-16212&&$asc<=-15641) return 'L';
        if($asc>=-15640&&$asc<=-15166) return 'M';
        if($asc>=-15165&&$asc<=-14923) return 'N';
        if($asc>=-14922&&$asc<=-14915) return 'O';
        if($asc>=-14914&&$asc<=-14631) return 'P';
        if($asc>=-14630&&$asc<=-14150) return 'Q';
        if($asc>=-14149&&$asc<=-14091) return 'R';
        if($asc>=-14090&&$asc<=-13319) return 'S';
        if($asc>=-13318&&$asc<=-12839) return 'T';
        if($asc>=-12838&&$asc<=-12557) return 'W';
        if($asc>=-12556&&$asc<=-11848) return 'X';
        if($asc>=-11847&&$asc<=-11056) return 'Y';
        if($asc>=-11055&&$asc<=-10247) return 'Z';
        return null;
    }
}