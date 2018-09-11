<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */

namespace app\api\controller;


class Pay extends Base{
    //检查权限作用域
    protected $beforeActionList  = [
        'checkPrimaryScope' => [
            'only' => 'pay'
        ],
    ];

    public function pay()
    {
        $result =  (new \app\api\logic\Pay())->pay();
        return $this->ajaxReturn($result);
    }
    public function notify()
    {
        $result =  (new \app\api\logic\Pay())->notify();
        return $this->ajaxReturn($result);
    }
}