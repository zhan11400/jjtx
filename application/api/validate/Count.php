<?php
/**
 * Class descript.
 * User: chan
 * Date: 2017/10/20 11:22
 */
namespace app\api\validate;

class Count extends BaseValidate
{
    protected $rule = [
        'count' => 'isPositiveInteger|between:1,15',
    ];
}
