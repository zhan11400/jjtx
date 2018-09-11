<?php
/**
 * 商户
 * User: chen guang
 * Date: 2017/9/29 9:00
 *
 */

namespace app\admin\Controller;


use think\Db;

class Set extends Common{

	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();

	}

	/**
	 * 首页分类
	 */
	public function type(){
		$list = db("index_type")->order("id asc")->select();
		$this->assign('list',$list);
		return $this->fetch();
	}
	/**
	 * 首页分类修改
	 */
	public function type_edit(){
		$id = input('id/d');
		if(empty($id)){
			$this->error("非法操作！");
		}
		$detail=array();
		if(request()->isPost()){
			$data['title']=input("title");
			$data['des']=trim(input('des'));
			$file=request()->file('images');
			if($file){
				$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($info){
					$data['images']= $info->getSaveName();
				}
			}
			$res=db("index_type")->where("id='$id'")->update($data);
			if($res===false) $this->error("操作失败！");
			$this->success("操作成功！");
		}
		$detail=db("index_type")->where("id='$id'")->find();
		$this->assign("data",$detail);
		return $this->fetch();
	}
	/**
	 * banner图
	 */
	public function banner(){
		$banner = db('banner');
		$ban = $banner->where("type=1")->select();
		$this -> assign("list",$ban);
		return $this->fetch();
	}

	/*
	 * 版块介绍
	 */
	public function introduce(){
		$list=db("index_introduce")->select();
		$this->assign("list",$list);
		return $this->fetch();
	}

	/*
      * 版块编辑
      */
	public function in_edit(){
		$id = input('id/d');
		if(empty($id)){
			$this->error('非法操作');
		}
		if(request()->isPost()){
			$file=request()->file('images');
			if($file){
				$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($info){
					$data['images']= $info->getSaveName();
				}
			}
			$data['title'] = input('title');
			$data['des']   = input('des');
			$res = db('index_introduce')->where("id='$id'")->update($data);
			if($res!==false){$this->success('操作成功');}else{$this->error('操作失败');}
		}
		$introduce = db('index_introduce')->where("id='$id'") ->find();
		$this -> assign("data",$introduce);
		return $this->fetch();
	}

	/*
      * 底部图编辑
      */
	public function foot_images(){
		if(request()->isPost()){
			$file=request()->file('images');
			if($file){
				$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($info){
					$data['images']= $info->getSaveName();
				}
			}
			$files=request()->file('images_bj');
			if($files){
				$infos = $files->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($infos){
					$data['images_bj']= $infos->getSaveName();
				}
			}
			$res = db('index_banner')->where("id=2")->update($data);
			if($res!==false){$this->success('操作成功');}else{$this->error('操作失败');}
		}
		$images = db('index_banner')->where("id=2") ->find();
		$this -> assign("data",$images);
		return $this->fetch();
	}
	/**
	 * 轮播图
	 */
	public function bannerlist(){
		$banner = db('banner');
		$ban = $banner->where("type=0")->select();
		$this -> assign("list",$ban);
		return $this->fetch();
	}
	/**
	 * 系统通知
	 */
	public function notice(){
		$banner = db('news_log');
		$ban = $banner->where("uid=0")->select();
		$this -> assign("list",$ban);
		return $this->fetch();
	}
	/**
	 * 轮播添加或修改
	 */
	public function notice_edit(){
		$id = input('id');
		$news_log=db('news_log')->where("id='$id'")->find();
		if(request()->isPost()){
			$data['content']=input('content');
			$data['create_time']=NOW_TIME;
			$data['type']=input("type");
			$goodsid=input("goodsid/a");
			if($data['type']==1){
				$data['relation_id']=$goodsid[0];
			}elseif($data['type']==2){
				$data['goods']=serialize($goodsid);
			}
				$id = db("news_log")->insertGetId($data);
				$where['status']=1;
				$member=db("member")->where($where)->select();
				$n_user = db('news_log');
				foreach($member as $v){
					$data['uid']=$v['id'];
					$res=$n_user->insert($data);
				}
			if($res!==false){$this->success('操作成功');}else{$this->success('操作失败');}
		}
		$goods= db("goods")->where("status=1")->order("id desc")->select();
		$this->assign('goods',$goods);
		$this -> assign("banner",$news_log);
		return $this->fetch();

	}
	/**
	 * 轮播添加或修改
	 */
	public function banner_edit(){
		$id = input('id');
		if(request()->isPost()){
			$data['status'] = input('post.status');
			$data['title'] = input('post.title');
			$data['sort'] = input('post.displayorder');
			$data['url'] = input('post.url');
			$file = request()->file('bnimage');
			if (!empty($file)) {
				$info = $file->move(ROOT_PATH . 'public/static/uploads');
				if ($info) {
					$data['pic'] = $info->getSaveName();
				} else {
					$this->success($file->getError());
				}
			} else {
				$data['pic'] = input('bne');
			}
			if($id){
				$bnr = db('banner')->where("id='$id'")->update($data);
			}else{
				$bnr = db('banner')->insert($data);
			}
			$this->success("操作成功!",url('Set/banner'));
		}
		$banner=db('banner')->where("id='$id'")->find();
		$this -> assign("banner",$banner);
		return $this->fetch();

	}
	/**
	 * 轮播删除
	 */
	public function banner_del(){
		$bnid = input('id');
		$banner = db('banner');
		$del = $banner -> where("id='$bnid'") -> delete();
		if($del){
			exit(json_encode(1));
		}else{
			exit(json_encode("删除失败"));
		}
	}
	public function notice_del(){
		$bnid = input('id');
		$banner = db('news_log');
		$del = $banner -> where("id='$bnid'") -> delete();
		if($del){
			exit(json_encode(1));
		}else{
			exit(json_encode("删除失败"));
		}
	}

	/*
  * 用户协议
  */
	public function set(){
		$id = 1;
		if($this->request->isPost()){
			if(db('contract')->where(['id' => $id])->update(['content' => htmlspecialchars(input('content'))]) === false) {
				$this->error('更新失败');
			}else{
				$this->redirect($_SERVER['HTTP_REFERER']);
			}
		}
		$detail=db("contract")->where("id='$id'")->find();
		if(!$detail) $this->error("记录不存在");
		$this->assign("data",$detail);
		return $this->fetch();
	}
	/*
  * 关于我们
  */
	public function about(){
		$id = 2;
		if($this->request->isPost()){
			if(db('contract')->where(['id' => $id])->update(['content' => htmlspecialchars(input('content'))]) === false) {
				$this->error('更新失败');
			}else{
				$this->redirect($_SERVER['HTTP_REFERER']);
			}
		}
		$detail=db("contract")->where("id='$id'")->find();
		if(!$detail) $this->error("记录不存在");
		$this->assign("data",$detail);
		return $this->fetch();
	}

	/**
	 * 设置
	 */
	public function index(){
		$list=db("config")->select();
		$this->assign('list',$list);
		return $this->fetch();
	}
	/**
	 * 设置
	 */
	public function deal(){
		$name=input('name');
		$value=input('value');
		if(!$name){echo -1;exit;};
		$where['id']=$name;
		$list=db("config")->where($where)->find();
		if(!$list) {echo -2;exit;};
		$res=db("config")->where($where)->update(array('value'=>$value));
		if(!$res) {echo -3;exit;};
		echo 1;exit;
	}
	/**
	 *   属性模板
	 */
	public function tpl(){
		$goods= db("goods")->where("status=1")->order("id desc")->select();
		$this->assign('goods',$goods);
		return $this->fetch();
	}
	/**setagentblack
	 *   搜索商品
	 */
	public function sou(){
		$key=input("key");
		$where['name']=array("like",'%'.$key.'%');
		$where['status']=1;
		$data=db("goods")->where($where)->select();
		$str="";
		foreach($data as $v){
			$str.= "<option value='".$v['id']."'>".$v['name']."</option>";
		}
		echo $str;
	}
}