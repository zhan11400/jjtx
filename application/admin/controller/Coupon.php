<?php
/**
 * 优惠劵
 * User: Tsang
 * Date: 2017/10/6 13:52
 *
 */

namespace app\admin\controller;


use app\admin\Controller\Common;

class Coupon extends Common
{

    private $m_coupon;
    private  $voucher;
    private  $info;

    public function __construct()
    {
        parent::__construct();
        $this->voucher = db('coupon');
    }

    /**
     * 抵扣券列表
     */
    public function index(){
        $list = $this->voucher->where(['delete'=>0])
            ->order('id desc')
            ->paginate(10);
        $count = $this->voucher->where(['delete'=>0])
            ->order('id desc')
            ->count();
        $this->assign('count',$count);
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 删除
     */
    public function delete(){
        $id = input('id/d');
        $del=$this->voucher->where(['id' => $id])->update(['delete' => 1]);
        if($del){
            exit(json_encode(1));
        }else{
            exit(json_encode("删除失败"));
        }
    }

    /**
     * 添加/修改
     */
    public function detail(){
        $id = input('id/d');
        if($id > 0){
            $coupon = $this->voucher->where(['id' => $id])->find();
            if(!$coupon) $this->error('找不到该优惠劵');
            $this->assign('data',$coupon);
        }
        if(request()->isPost()){

            $data = array(
                'name'      => input('name/s'),
                'status'           => input('status/d'),
                'count'    => input('num/d'),
                'deduction' => input('deduction/d'),
                'fullmoney'     => input('quota/d'),
                'useful_day'       => input('day/d'),
            );
            $file=request()->file('goods_image');
            if($file){
                $info = $file->move(ROOT_PATH . 'public/static/uploads');
                if($info){
                    $data['image']=$info->getSaveName();
                }
            }
            if($id > 0){
                if($this->voucher->where(['id' => $id])->update($data) === false) $this->error('修改失败');
            }else{
                $data['create_time'] = TIMESTAMP;
                if(!$this->voucher->insert($data)) $this->error('添加失败');
            }
            $this->redirect(url('coupon/index'));
        }
        return $this->fetch();
    }
}