<?php
/**
 * 跨域类.
 * Member: chan
 * Date: 2017/9/1 11:36
 */
namespace app\common\behavior;

class CORS
{
    public function run(&$params)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        if(request()->isOptions()){
            exit();
        }
    }
}