<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
Route::group('api',function(){

    // +----------------------------------------------------------------------
    // | 收货地址
    // +----------------------------------------------------------------------
    Route::group('address',function(){
        Route::post('detail','api/address/detail');//收货地址详情
        Route::post('list','api/address/address_list');//收货地址列表
        Route::post('default','api/address/setdefault');//设置默认收货地址
        Route::post('del','api/address/del');//删除收货地址
        Route::post('edit','api/address/edit');//添加(修改)收货地址
    });

    // +----------------------------------------------------------------------
    // | 登录注册
    // +----------------------------------------------------------------------
    Route::group('login',function(){
        Route::post('login','api/login/login');                                 //登录
        Route::post('register','api/login/register');                           //注册
        Route::post('forget','api/login/forget');                               //忘记密码
        Route::post('otherlogin','api/login/otherlogin');                       //第三方
        Route::post('perfect','api/login/perfect');                             //完善资料
        Route::post('changeInformation','api/login/changeInformation');         //修改资料
        Route::post('bind','api/login/bind');                                   //换手机号
    });
    // +----------------------------------------------------------------------
    // | 购物车
    // +----------------------------------------------------------------------
    Route::group('cart',function(){
        Route::post('add','api/cart/cart_add');//添加购物车
        Route::post('update','api/cart/update');//更新购物车数量，规格
        Route::post('remove','api/cart/remove');//删除购物车
        Route::post('select','api/cart/select');//选择要下单支付的产品
        Route::post('index','api/cart/index');//购物车列表
    });
    // +----------------------------------------------------------------------
    // | 商品
    // +----------------------------------------------------------------------
    Route::group('goods',function(){
        Route::post('category','api/goods/category');//分类
        Route::get('banner','api/goods/banner');//轮播图，规格
        Route::post('detail','api/goods/detail');//商品详情
        Route::get('comment','api/goods/comment');//商品评论列表
        Route::get('spec','api/goods/spec');//规格列表
        Route::get('option','api/goods/option');//选定规格

        Route::post('modelchild','api/goods/modelchild');//车型三四级
        Route::post('model','api/goods/model');//车型一二级
    });

    // +----------------------------------------------------------------------
    // | 首页
    // +----------------------------------------------------------------------
    Route::group('index',function(){
        Route::get('category','api/index/category');//分类
        Route::get('banner','api/index/banner');//轮播图，规格
        Route::get('recommend','api/index/recommend');//精品专区
        Route::get('brand','api/index/brand');//热门品牌
        Route::get('spec','api/index/spec');//今日爆款
        Route::get('contract','api/index/contract');//注册协议，关于我们
    });
    // +----------------------------------------------------------------------
    // | 收藏
    // +----------------------------------------------------------------------
    Route::group('collect',function(){
        Route::get('goods','api/collect/collect_goods_list');//收藏列表
        Route::post('collect','api/collect/collect_goods');//收藏店铺与取消
        Route::post('remove','api/collect/collect_goods_remove');//删除商品收藏
    });

    // +----------------------------------------------------------------------
    // | 订单
    // +----------------------------------------------------------------------
    Route::group('order',function(){
        Route::post('confirm','api/order/order_confirm');//确认订单
        Route::post('create','api/order/order_create');//创建订单
        Route::post('pay','api/order/order_pay');//订单支付
        Route::post('cancel','api/order/order_cancel');//取消订单
        Route::post('receive','api/order/order_receive');//确认收货
        Route::post('deleted','api/order/deleted');//删除订单
     /*   Route::group('evaluate',function(){
            Route::get('','api/order/evaluate_info');//评价商品信息
            Route::post('write','api/order/order_evaluate');//订单评价
        });*/
        Route::post('refund','api/order/order_refund');//申请退款
        Route::post('refund_detail','api/order/order_refund_detail');//申请退款
        Route::post('list','api/order/order_list');//订单列表
        Route::post('logistics','api/order/logistics');//查看物流
        Route::post('detail','api/order/order_detail');//订单详情
        Route::post('remind','api/order/remind');//提醒发货
    });
    // +----------------------------------------------------------------------
    // | 经销商
    // +----------------------------------------------------------------------
    Route::group('agent',function(){
        Route::post('index','api/agent/index');//经销商中心
        Route::post('shop','api/agent/shop');//店铺
        Route::post('order','api/agent/order');//经销商订单
        Route::post('deal','api/agent/deal');//审核
        Route::post('getagent','api/index/getagent');//获取自己的经销商
    });
// +----------------------------------------------------------------------
    // | 个人中心
    // +----------------------------------------------------------------------
    Route::group('center',function(){
        Route::post('index','api/center/index');//个人中心
        Route::post('tip','api/center/tip');//信息
        Route::post('credit','api/center/credit');//信用额
        Route::post('credit_log','api/center/credit_log');//信用额记录
    });
    // +----------------------------------------------------------------------
    // | 短信
    // +----------------------------------------------------------------------
    Route::group('msn',function(){
        Route::post('index','api/msn/index');//短信
    });
});
