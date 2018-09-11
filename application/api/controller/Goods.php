<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/11/10  13:38
 */
namespace app\api\controller;
class Goods extends Base{
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => [
            'only' => ''//except
        ],
    ];
    /**
     * 商品列表
     */
    public function index()
    {
        $result =  (new \app\api\logic\Goods())->index();
        return $this->ajaxReturn($result);
    }
    /**
     * 分类列表
     */
    public function category()
    {
        $result =  (new \app\api\logic\Goods())->category();
        return $this->ajaxReturn($result);
    }
    /**
     * 热门品牌
     */
    public function brand()
    {
        $result =  (new \app\api\logic\Goods())->brand();
        return $this->ajaxReturn($result);
    }
    /**
     * 详情
     */
    public function detail()
    {
        $result =  (new \app\api\logic\Goods())->detail();
        return $this->ajaxReturn($result);
    }
    /**
     * 评论列表
     */
    public function comment()
    {
        $result =  (new \app\api\logic\Goods())->comment();
        return $this->ajaxReturn($result);
    }
    /**
     * 规格列表
     */
    public function spec()
    {
        $result =  (new \app\api\logic\Goods())->spec();
        return $this->ajaxReturn($result);
    }
    /**
     * 确定选择规格
     */
    public function option()
    {
        $result =  (new \app\api\logic\Goods())->option();
        return $this->ajaxReturn($result);
    }
    /**
     * 车型
     */
    public function model()
    {
        $result =  (new \app\api\logic\Goods())->model();
        return $this->ajaxReturn($result);
    }
    /**
     * 车型子分类
     */
    public function modelchild()
    {
        $result =  (new \app\api\logic\Goods())->modelchild();
        return $this->ajaxReturn($result);
    }
    /**
     * 搜索页面
     */
    public function search()
    {
        $result =  (new \app\api\logic\Goods())->hot_search();
        return $this->ajaxReturn($result);
    }
    /**
     * 删除
     */
    public function del_search()
    {
        $result =  (new \app\api\logic\Goods())->del_search();
        return $this->ajaxReturn($result);
    }
}