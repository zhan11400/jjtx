<?php

/**
 * 首页
 * User: tsang
 * Date: 2017/9/14
 * Time: 20:11
 */

namespace app\admin\Controller;
use think\Db;
use think\Request;

class Goods extends Common
{
	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();
	}

	/*
	*	商品列表
	*/
    public function lists()
    {	
    	$where=array();
    	$keyword = input('keyword');

		if($keyword){
			$where['name']=array("like",'%'.$keyword.'%');
		}

		$list = db("goods")->alias('g')
				->join('goods_category gc','gc.id=g.cid')
				->where($where)->order("id desc")->field("g.id,g.name,g.image,gc.name as category_name")->paginate(10);

		$config_uploads = config('url.uploads');
		//IMG_PATH
		$this->assign('config_uploads',$config_uploads);
    	$this->assign('keyword',$keyword);
    	$this->assign('list',$list);
        return $this->fetch();
    }


	/*
	*	商品添加
	 */
	public function add_goods()
	{
		$id=input('id');

		if(request()->isPost()){
			$images=input('photo/a');
			$is_sell_out=input('is_home');
			$status=input('status');
			$key=input("key/a");
			$param=input("param/a");
			$file=Request::instance()->file('goods_image');
			if($file){
				$info = $file->move(ROOT_PATH . 'public/static/uploads');

				if($info){
					$data['image']= "/".str_replace('\\',"/",$info->getSaveName());
				}
			}
			$files = request()->file('photoname');
			if (!empty($files)) {
				foreach($files as $file){
					// 移动到框架应用根目录/public/uploads/ 目录下
					$info = $file->move(ROOT_PATH . 'public/static/uploads');
					if($info){
						if($images) {
							array_push($images, $info->getSaveName());
						}else{
							$images[] = $info->getSaveName();
						}
					}
				}
			}
			if($images){
				$data['images']=serialize($images);
			}
			if(!empty($key)){
				$option=array();
				foreach($key as $k=>$v){
					$option[$k]['key']=$v;
					$option[$k]['value']=$param[$k];
				}
				$data['option']=serialize($option);
			}
			$data['cid'] =  input('cid/d');
			$data['cid'] =  input('cid/d');
			$data['unit']= input('unit');
			$data['brand'] = input("brand");
			$data['name'] = input('name');
			$data['des'] =input('des');
			$data['vin']=input("vin");
			$data['content'] =input('content');
			$data['sort'] =input('sort');
			$model=array_merge(input("model1/a"),input("model2/a"),input("model3/a"),input("model4/a"));
			$model=array_unique($model);
			$data['modelstr']=implode(",",$model);
			$model1=input("model1/a");
			$model2=input("model2/a");
			$model3=input("model3/a");
			$model4=input("model4/a");
			if(!empty($model1)){
				$model_a=array();
				foreach($model1 as $k=>$v){
					$model_a[$k]['model1']=$v;
					$model_a[$k]['model2']=$model2[$k];
					$model_a[$k]['model3']=$model3[$k];
					$model_a[$k]['model4']=$model4[$k];
				}

				$data['model']=serialize($model_a);
			}
			if(empty($id)){
				db('goods')->insert($data);
				$goods_id = db('goods')->getLastInsID();
			}else{
				$data['is_home'] = $is_sell_out;
				$data['status'] = $status;
				db('goods')->where(array('id'=>$id))->update($data);
				$goods_id = $id;
			}

			//商品规格更新
			$this->goods_specification($goods_id);

			$this->success("操作成功!",url('Goods/lists'));
		}

		if(!empty($id)){
			//查询商品
			$goods_result = db('goods')->where(array('id'=>$id))->find();
			$goods_result['did'] = db("goods_category")->field("name,id")->where("id=".$goods_result['did'])->find();
			$goods_result['images'] = unserialize($goods_result['images']);
			$goods_result['option'] = unserialize($goods_result['option']);
			$goods_result['model'] = unserialize($goods_result['model']);
			$db_brand=db("goods_model");
			foreach($goods_result['model'] as &$de){
				 $de['model1']=$db_brand->field("name,id")->where("id=".$de['model1'])->find();
				$de['model2']=$db_brand->field("name,id")->where("id=".$de['model2'])->find();
				$de['model3']=$db_brand->field("name,id")->where("id=".$de['model3'])->find();
				$de['model4']=$db_brand->field("name,id")->where("id=".$de['model4'])->find();
			}
			//查询商品规格大分类
			$option_category_result = db('goods_option_category')->where(array('goodsid'=>$id))->order("id asc")->select();

			//查询小分类值
			$goods_option = db('goods_option')->where(array('goodsid'=>$id))->order("id asc")->select();

			foreach($goods_option as $key => $value){
				if($value['option_category'] == $option_category_result[0]['id']){
					$attrname[]=$value;
				}

				if(isset($option_category_result[1]['id'])){
					if($value['option_category'] == $option_category_result[1]['id']){
						$attrvalue[]=$value;
					}
				}
			}

			//查询options值
			$goods_option_value_result = db('goods_option_value')->where(array('goodsid'=>$id))->order("id asc")->select();
			$goods_option_value_count = count($goods_option_value_result);

			if(isset($option_category_result[1]['id'])){
				$attrvalue_count = count($attrvalue);
				$this->assign('attrvalue',$attrvalue);
				$this->assign('attrvalue_count',$attrvalue_count);
			}

			$goods_option_count = count($goods_option);
			$data=db("goods_model")->where("status=1 and pid=0")->select();
			$goods_brand=db("goods_brand")->where("status=1")->select();
			$this->assign('goods_model',$data);
			$this->assign('goods_brand',$goods_brand);
			$this->assign('goods_result',$goods_result);
			$this->assign('option_category_result',$option_category_result);
			$this->assign('goods_option_count',$goods_option_count);
			$this->assign('attrname',$attrname);
			$this->assign('goods_option_value_result',$goods_option_value_result);
			$this->assign('goods_option_value_count',$goods_option_value_count);
		}


		//查询商品分类
		$goods_category = db('goods_category')->select();

		//查询配置文件图片地址
		$config_uploads = config('url.uploads');
		$goods_brand=db("goods_brand")->where("status=1")->select();
		$this->assign('id',$id);
		$this->assign('goods_brand',$goods_brand);
		$this->assign('config_uploads',$config_uploads);
		$this->assign('goods_category',$goods_category);
		return $this->fetch();
	}

	public function goods_specification($goods_id){
		//商品规格
		$attrtitleone = input('attrtitleone');
		$option_one = input('option_one/a');
		$attrtitletow = input('attrtitletow');
		$option_two = input('option_two/a');
		$attroldprice = input('attroldprice/a');
		$attrprice = input('attrprice/a');
		$attrtotal = input('attrtotal/a');

		//先清空表数据
		db('goods_option_category')->where(array('goodsid'=>$goods_id))->delete();
		db('goods_option')->where(array('goodsid'=>$goods_id))->delete();
		db('goods_option_value')->where(array('goodsid'=>$goods_id))->delete();

		$input = input();
		//var_dump($input);die;

		//大分类插入
		$attrtitleone_data[] = $attrtitleone;

		if(!empty($attrtitletow)){
			$attrtitleone_data[] = $attrtitletow;
		}

		foreach($attrtitleone_data as $key=>$value){
			//规格插入
			$goods_option_category_data['goodsid'] = $goods_id;
			$goods_option_category_data['title'] = $value;
			db('goods_option_category')->insert($goods_option_category_data);
			$goods_option_category_id[] = db('goods_option_category')->getLastInsID();
		}

		//小分类插入
		$option[] = $option_one;

		if(!empty($option_two)){
			$option[] = $option_two;
		}

		foreach ($option as $key => $value) {
			foreach($value as $k=>$v){
				$goods_option_data['goodsid'] = $goods_id;
				$goods_option_data['option_category'] = $goods_option_category_id[$key];
				$goods_option_data['title'] = $v;

				db('goods_option')->insert($goods_option_data);
			}
		}

		//拼接
		if(!empty($option_two)){
			foreach($option_one as $v){
				foreach($option_two as $d){
					$spec_name[]=$v.'+'.$d;
				}
			}
		}

		foreach($attrprice as $key => $value){
			$goods_option_value_data['goodsid'] = $goods_id;
			$goods_option_value_data['price'] = $attrprice[$key];
			$goods_option_value_data['oldprice'] = $attroldprice[$key];
			$goods_option_value_data['stock'] = $attrtotal[$key];

			$where['goodsid'] = $goods_id;
			if(!empty($option_two)){
				$specs_array = explode("+", $spec_name[$key]);
				$where['title'] = $specs_array[0];
				$goods_option_value_data['spec_name'] = $spec_name[$key];
			}else{
				$where['title'] = $option_one[$key];
				$goods_option_value_data['spec_name'] = $option_one[$key];
			}

			$goods_option_category_id = db('goods_option')->where($where)->field('id')->find();

			if(isset($specs_array[1])){
				$goods_option_id = db('goods_option')->where(array('goodsid'=>$goods_id,'title'=>$specs_array[1]))->field('id')->find();
				$goods_option_value_data['specs'] = $goods_option_category_id['id'].','.$goods_option_id['id'];
			}else{
				$goods_option_value_data['specs'] = $goods_option_category_id['id'];
			}

			db('goods_option_value')->insert($goods_option_value_data);
		}
	}
	/**
	 *   选择二级分类
	 */
	public function select(){
		$where['pid']=input("id");
		$where['status']=1;
		$goods_category = db('goods_category')->where($where)->select();
		$str='';
		foreach($goods_category as &$v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
	/**
	 *   属性模板
	 */
	public function tpl(){

		return $this->fetch();
	}
	/**
	 *   套餐属性模板
	 */
	public function posttpl(){
		$data=db("goods_model")->where("status=1 and pid=0")->select();
		$this->assign("data",$data);
		return $this->fetch();
	}
/**
*   二分类
*/
	public function cate(){
		$pid=input('pid');
		$data=db("goods_category")->where("status=1 and pid='$pid'")->select();
		$str="<option value='0'>请选择二级分类</option>";
		foreach($data as $v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
	/**
	 *   model2
	 */
	public function model2(){
		$pid=input('pid');
		$data=db("goods_model")->where("status=1 and pid='$pid'")->select();
		$str="<option value='0'>请选择车型</option>";
		foreach($data as $v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
	/**
	 *   model2
	 */
	public function model3(){
		$pid=input('pid');
		$data=db("goods_model")->where("status=1 and pid='$pid'")->select();
		$str="<option value='0'>请选择排量</option>";
		foreach($data as $v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
	/**
	 *   model2
	 */
	public function model4(){
		$pid=input('pid');
		$data=db("goods_model")->where("status=1 and pid='$pid'")->select();
		$str="<option value='0'>请选择年份</option>";
		foreach($data as $v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
	/*
		商品分类列表
	 */
	public function category_lists()
	{	
		$where = array();
		$keyword = input('keyword');
		if($keyword){
			$where['name']=array("like",'%'.$keyword.'%');
		}
		if(input("pid")){
			$where['pid']=input("pid");
		}else{
			$where['pid']=0;
		}
		$list = db("goods_category")->where($where)->order("id desc")->select();
		$this->assign('keyword',$keyword);
		$this->assign('list',$list);
		$this->assign('pid',intval(input("pid")));
		return $this->fetch();
	}
	/*
            车型列表
         */
	public function model()
	{
		$where = array();
		$keyword = input('keyword');
		if($keyword){
			$where['name']=array("like",'%'.$keyword.'%');
		}
		if(input("pid")){
			$where['pid']=input("pid");
		}else{
			$where['pid']=0;
		}
		$list = db("goods_model")->where($where)->order("id desc")->select();
		$this->assign('keyword',$keyword);
		//echo '<pre>';
		//print_r($this->getTree($list,0));exit;
		$this->assign('list',$list);
		$this->assign('pid',intval(input("pid")));
		return $this->fetch();
	}
 public	function getTree($data, $pId)
	{
		$tree = '';
		foreach($data as $k => $v)
		{
			if($v['pid'] == $pId)
			{        //父亲找到儿子
				$v['child'] =$this-> getTree($data, $v['id']);
				$tree[] = $v;
				//unset($data[$k]);
			}
		}
		return $tree;
	}

	/*
            品牌列表
         */
	public function brand()
	{
		$where = array();
		$keyword = input('keyword');
		if($keyword){
			$where['name']=array("like",'%'.$keyword.'%');
		}
		$list = db("goods_brand")->where($where)->order("id desc")->select();
		$this->assign('keyword',$keyword);
		$this->assign('list',$list);
		$this->assign('pid',intval(input("pid")));
		return $this->fetch();
	}
	/*
添加修改品牌
*/
	public function add_model()
	{

		$id = input('id/d');
		$data = array();
		if(request()->isPost()){
			$data['name']=input("name");
			$data['pid'] = input('pid/d');
			$file = request()->file('image');
			if($file){
				$info = $file->move(ROOT_PATH . 'public/static/uploads');
				if($info){
					$data['image']= $info->getSaveName();
				}
			}
			if(!empty($id)){
				$data['status'] = input("status");
				$res=db("goods_model")->where("id='$id'")->update($data);
			}else{
				$res=db("goods_model")->insert($data);
			}
			if(!$res) $this->error("操作失败！");
			$this->success("操作成功！" ,url("goods/model",array("pid"=>$data['pid'])));
		}
		$goods_result = db("goods_model")->where("id='$id'")->find();
		if(input('pid/d')){
			$pid=input('pid/d');
		}else{
			$pid=$goods_result['pid'];
		}
		$this->assign('goods_result',$goods_result);
		$this->assign('pid',$pid);

		return $this->fetch();
	}
	/*
    添加修改品牌
 */
	public function add_brand()
	{

		 $id = input('id/d');
		$data = array();
		if(request()->isPost()){
			$data['name']=input("name");
			$file = request()->file('image');
			if($file){
				$info = $file->move(ROOT_PATH . 'public/static/uploads');
				if($info){
					$data['image']= $info->getSaveName();
				}
			}
			if(!empty($id)){
				$data['status'] = input("status");
				$res=db("goods_brand")->where("id='$id'")->update($data);
			}else{
				$res=db("goods_brand")->insert($data);
			}
			if(!$res) $this->error("操作失败！");
			$this->success("操作成功！" ,$_SERVER['HTTP_REFERER']);
		}

		$goods_result = db("goods_brand")->where("id='$id'")->find();
		$this->assign('goods_result',$goods_result);
		return $this->fetch();
	}
	/*
		添加商品分类
	 */
	public function add_category()
	{

		$id = input('id/d');
		$data = array();
		$pid = input('pid/d');
		if(request()->isPost()){
			$data['name']=input("name");
			$data['pid'] = $pid;
			$file = request()->file('image');
			if($file){
				$info = $file->move(ROOT_PATH . 'public/static/uploads');
				if($info){
					$data['image']= $info->getSaveName();
				}
			}
			if(!empty($id)){
				$data['status'] = input("status");
				$res=db("goods_category")->where("id='$id'")->update($data);
			}else{
				$res=db("goods_category")->insert($data);
			}
			if(!$res) $this->error("操作失败！");
			$this->success("操作成功！" ,$_SERVER['HTTP_REFERER']);
		}

		$goods_result = db("goods_category")->where("id='$id'")->find();
		$this->assign('goods_result',$goods_result);
		return $this->fetch();
	}

	/*
		删除商品分类
	 */
	public function del_category()
	{
		$id = input('id/d');

		$res=db("goods_category")->where("id='$id'")->update(array('status'=>'-1'));

		if($res) $this->success("操作成功");
		$this->error("操作失败");
	}
	
}