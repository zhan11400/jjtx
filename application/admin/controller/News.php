<?php
/**
 * 商户
 * User: chen guang
 * Date: 2017/9/29 9:00
 *
 */

namespace app\admin\Controller;


use think\Db;

class News extends Common{
	
	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();

	}

    /**
     * 新闻分类
     */
    public function type(){
        $list = db("news_type")->order("id asc")->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
	/**
    * 新闻分类修改
    */
    public function type_edit(){
    	$id = input('id/d');
		if(empty($id)){
			$this->error("非法操作！");
		}
		$detail=array();
		if(request()->isPost()){
			$data['name']=input("name");
			$data['english']=input("english");
			$file=request()->file('images');
			if($file){
				$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($info){
					$data['images']= $info->getSaveName();
				}
			}
			$res=db("news_type")->where("id='$id'")->update($data);
			if($res===false) $this->error("操作失败！");
			$this->success("操作成功！");
		}
		$detail=db("news_type")->where("id='$id'")->find();
		$this->assign("data",$detail);
    	return $this->fetch();
    }
	/**
     * 新闻列表
     */
    public function lists(){
    	$type=input("type");
		$where = array();
		if($type!=''){
			$where['type']=$type;
		}
		if(input("keyword")){
			$where['title']=array("like",'%'.input('keyword').'%');
		}
		$list = db("news")->where($where)->order("id desc")->paginate(6);
    	$res = db("news_type")->select();
		$this->assign("res",$res);
		$this->assign('type',input("type"));
        $this->assign('list',$list);
		$this->assign('keyword',input("keyword"));
        return $this->fetch();
    }
	/**
    * 新闻修改/添加
    */
    public function add(){
    	$id = input('id/d');
		$detail=array();
		if(request()->isPost()){
			$data['title']=input("title");
			$data['name'] =input("name");
			$data['des']  =input("des");
			$data['time'] =time();
			$data['type'] =input("type/d");
			$file=request()->file('images');
			if($file){
				$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads');
				if($info){
					$data['images']= $info->getSaveName();
				}
			}
			if(!empty($id)){
				$res=db("news")->where("id='$id'")->update($data);
			}else{
				$res=db("news")->insert($data);
			}
			if($res===false) $this->error("操作失败！");
			$this->success("操作成功！");
		}
		$detail=db("news")->where("id='$id'")->find();
		$this->assign("data",$detail);
		$res = db("news_type")->select();
		$this->assign("res",$res);
    	return $this->fetch();
    }
	/*
  * 删除会员
  */
	public function del(){
		$id = input('id/d');
		$res=db("news")->where("id='$id'")->delete();
		if($res) $this->success("操作成功");
		$this->error("操作失败");
	}
}