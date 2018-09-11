<?php
/**
 * 验证类的基类
 * Member: chan
 * Date: 2017/8/4 Time: 19:05
 */
namespace app\api\validate;

use app\api\service\Token;
use app\lib\exception\ForbiddenException;
use app\lib\exception\ParameterException;
use think\Request;
use think\Validate;

class BaseValidate extends Validate
{
    /**
     * 检测所有客户端发来的参数是否符合验证类规则
     * 基类定义了很多自定义验证方法
     * 这些自定义验证方法其实，也可以直接调用
     * @throws ParameterException
     * @return true
     */
    public function checkParam()
    {
        $request = Request::instance();
        $params = $request->param();

        if (!$this->check($params)) {
            $returnData = json_encode([
                'data' => array(),
                'code' => -1,
                'message' => is_array($this->error) ? implode(';', $this->error) : $this->error,
            ]);
            exit($returnData);
        }
        return true;
    }

    protected function isPositiveInteger($value, $rule='', $data='', $field='')
    {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
            return true;
        }
        return $field . '必须是正整数';
    }

    protected function isNotEmpty($value, $rule='', $data='', $field='')
    {
        if (empty($value)) {
            return $field . '不能为空';
        } else {
            return true;
        }
    }
    /**
     * 检查手机号码格式
     * @param $value 手机号码
     */
    protected function isMobile($value)
    {
        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {

            return false;
        }
    }

    /**
     * 检查固定电话
     * @param $value
     * @return bool
     */
    protected function check_telephone($value)
    {
        if(preg_match('/^([0-9]{3,4}-)?[0-9]{7,8}$/',$value))
            return true;
        return false;
    }

    /**
     * 检查邮箱地址格式
     * @param $email 邮箱地址
     */
    protected function check_email($email)
    {
        if(filter_var($email,FILTER_VALIDATE_EMAIL))
            return true;
        return false;
    }

    function __destruct()
    {
        $this->checkParam();
    }
}