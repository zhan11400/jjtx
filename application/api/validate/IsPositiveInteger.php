<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */
namespace app\api\validate;
class IsPositiveInteger extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
    ];
}
