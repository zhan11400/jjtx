<?php
/**
 * 常量定义.
 * User: chan
 * Date: 2017/8/14 13:37
 */
define('TIMESTAMP',time());
// 定义host
define('__ROOT__',"http://".$_SERVER['HTTP_HOST']);
define('IMG_PATH',"http://".$_SERVER['HTTP_HOST'] . '/static/uploads/');
// 定义时间
define('NOW_TIME',$_SERVER['REQUEST_TIME']);



define('ARTICLE_STATUS_DELETE','-1');//删除
define('ARTICLE_STATUS_NORMAL','0');//待发布
define('ARTICLE_STATUS_SUCCESS','1');//正常


// +----------------------------------------------------------------------
// | 订单状态
// +----------------------------------------------------------------------
define('ORDER_STATUS_NOPAY','0');//0待付款
define('ORDER_STATUS_PAID','1');//1已付款
define('ORDER_STATUS_SUCCESS','2');//2已完成
define('ORDER_STATUS_CLOSE','-1');//-1已关闭