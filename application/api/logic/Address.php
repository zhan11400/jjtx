<?php
/**
 * 收货地址
 * member: Administrator
 * Date: 2017/8/4
 * Time: 16:16
 */

namespace app\api\logic;

use app\api\BaseModel;
use think\Model;
use think\Db;

class Address extends BaseModel
{
    
    private $m_address;
    
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->m_address = db('user_address');
    }


    //添加收货地址(修改收货地址)
    public function edit()
    {
        $res = $this->checkData(
            ["name","mobile","province","city","county","address"],
            ["收货人姓名不能为空","收货人手机号码不能为空","省份不能为空","城市不能为空","区域不能为空","收货地址不能为空"]
        );
        if(array_key_exists('message',$res)) return $res;
        $name = $this->requestData["name"];
        $mobile = $this->requestData["mobile"];
        $province = $this->requestData["province"];
        $city = $this->requestData["city"];
        $area = $this->requestData["county"];
        $address = $this->requestData["address"];
        $data = array(
            'name'        => $name,
            'mobile'      => $mobile,
            'province'    => $province,
            'city'        => $city,
            'country'     => $area,
            'detail'      => $address,
            'user_id'     => $this->user['uid'],
        );
        if (!array_key_exists('id',$this->requestData)) {
            $count = $this->m_address->where(["user_id" => $this->user['uid']])->count();
            if ($count > 20) {
                return array('message' => '个人收货地址不能超过20条');
            }
            if($count == 0){
                $data['isdefault'] = 1;
            }
            if (!$this->m_address->insert($data)) return array('message' => '添加失败');
        } else {
            if ($this->m_address->where(['address_id' => intval($this->requestData['id'])])->update($data) === false) return array('message' => '更新失败');
        }
        return array();
    }

    //设置默认收货地址
    public function setdefault()
    {
        $res = $this->checkData(
            ["id"],
            ["收货地址ID不能为空"]
        );
        if(array_key_exists('message',$res)) return $res;
        $addressid = intval($this->requestData["id"]);
        $sel = $this->m_address->where(["address_id" => $addressid])->find();
        if (!$sel) {
            return array('message' => "收货地址不存在");
        }
        Db::startTrans();
        try{

            if($this->m_address->where(["user_id" => $this->user['uid']])->update(['isdefault' => 0]) === false) throw new \Exception('更新失败');

            if($this->m_address->where(["user_id" => $this->user['uid'],"address_id" => $addressid])->update(['isdefault' => 1]) === false) throw new \Exception('更新失败');
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return array('message' => '设置失败');
        }
        return array();
    }

    //删除收货地址
    public function del()
    {
        $res = $this->checkData(
            ["id"],
            ["收货地址ID不能为空"]
        );
        if(array_key_exists('message',$res)) return $res;
        $addressid = intval($this->requestData["id"]);
        $sel = $this->m_address->where(["address_id" => $addressid,"user_id" => $this->user['uid']])->find();
        if (!$sel) {
            return array('message' => "收货地址不存在");
        }
        if (!$this->m_address->where(["user_id" => $this->user['id'],"address_id" => $addressid])->delete()) return array('message' => "删除失败");
        return array();
    }

    //收货地址详情
    public function detail()
    {
        $res = $this->checkData(
            ["id"],
            ["收货地址ID不能为空"]
        );
        if(array_key_exists('message',$res)) return $res;
        $addressid = intval($this->requestData["id"]);
        $sel = $this->m_address->where(["address_id" => $addressid,'user_id' => $this->user['uid']])->find();
        if (!$sel) {
            return array('message' => "收货地址不存在");
        }
        return $sel;
    }

    //收货地址列表
    public function address_list()
    {
        $sel = $this->m_address->where(["user_id" => $this->user['uid']])->select();
        return $sel;
    }
    
}
