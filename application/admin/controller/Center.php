<?php
/**
 * 会员中心
 * User: chen guang
 * Date: 2017/9/29 9:00
 *
 */

namespace app\admin\Controller;
use app\admin\Controller\Export;
use think\Db;

	class Center extends Common{

	/*
	*	构造函数
	*/
	function __construct(){
		parent::__construct();

	}

	/**
	 * 会员列表
	 */
	public function member(){
		$status=input("status");
		if($status!=''){
			$where['status']=$status;
		}else{
			$where['status']=array('gt',-2);
		}
		if(!empty(input('agentid'))) {
            $where['agent'] = input('agentid');
        }
		$type=input("type");
		if($type!='' && $type!=3){
			$where['type']=$type;
		}
		$ids = db("agent")->where("status=1")->order("id desc")->column("uid");
		$str=implode(",",$ids);
		if($type==3){
			$where['id']=array("in",$str);
		}
		if(input("keyword")){
			$where['nickname|mobile']=array("like",'%'.input('keyword').'%');
		}
		if(input("export")){
			$this->export($where);exit;
		}
		$list = db("member")->where($where)->order("id desc")->paginate(8);
		$list->each(function($i) use ($ids){
			if(in_array($i['id'],$ids)){
			    $i['type']=3;
			}
			return $i;
		});
		$count = db("member")->where($where)->order("id desc")->count();
		$this->assign('list',$list);
		$this->assign('count',$count);
		$this->assign('status',input("status"));
		$this->assign('type',input("type"));
		$this->assign('keyword',input("keyword"));
		return $this->fetch();
	}
		/**
		 * 经销商列表
		 */
		public function agent(){
			$where=array();
			if(input("keyword")){
				$where['name|realname|mobile']=array("like",'%'.input('keyword').'%');
			}
			if(input("status")!=''){
				$where['status']=input("status");
			}
			if(input("export")){
				$this->exportagent($where);exit;
			}
			$list = db("agent")->where($where)->order("id desc")->paginate(10);
			$count = db("agent")->where($where)->order("id desc")->count();
			$this->assign('list',$list);
			$this->assign('count',$count);
			$this->assign('keyword',input("keyword"));
			$this->assign('status',input("status"));
			return $this->fetch();
		}
		/**
		 * 经销商列表导出excel
		 */
		public function exportagent($where)
		{

			$headArr = array("经销商ID","经销商名字","真实姓名","手机号码","省份","城市","区/县","时间",'成交金额','成交订单数',"状态");
			//$where['status']=1;
			$list = db('agent')->where($where)->select();
			$order=db("order");
			$data=array();
			foreach($list as $k => $v){
				$data[$k][] = $v['id'];
				$data[$k][] = $v['name'];
				$data[$k][] = $v['realname'];
				$data[$k][] = $v['mobile'];
				$data[$k][] = $v['province'];
				$data[$k][] = $v['city'];
				$data[$k][] = $v['county'];
				$data[$k][] = date("Y-m-d H:i",$v['create_time']);
				$data[$k][] =$order->where("agentid=".$v['id']." and status='3'")->sum("price");
				$data[$k][] =$order->where("agentid=".$v['id']." and status='3'")->count();
				if($v['status']==0){
					$data[$k][]='拉黑中';
				}elseif($v['status']==1){
					$data[$k][]='正常状态';
				}
			}
			$filename='经销商列表';
			(new Export())->ImportExcel($filename,$headArr,$data);
		}
		/**
		 * 添加经销商
		 */
		public function add(){
			$id=input("id");
			if(request()->isPost()){
				if(empty(input('mobile')) || empty(input('password'))){
					$this->error('手机号或密码为空');
				}
				$where['mobile']=input('mobile');
				$list=db("agent")->where($where)->find();
				if($list && $id!=$list['id']) {
					$this->error('手机号已存在');
				}
				$eee['uid']=input('uid');
				$list=db("agent")->where($eee)->find();
				if($list  && $id!=$list['id']) {
					$this->error('该用户已经是分销商');
				}
				$ddd['name']=input('name');
				$list=db("agent")->where($ddd)->find();
				if($list && $id!=$list['id']) {
					$this->error('该代理商名称已存在');
				}
				$data['mobile']=input('mobile');
				$data['uid']=input('uid');
				$data['password']=md5(input('password'));
				$data['realname']=input('realname');
				$data['name']=input('name');
				$data['province']=input('province');
				$data['city']=input('city');
				$data['county']=input('county');
				$data['status']=1;
				if(empty($id)) {
					$data['create_time']=TIMESTAMP;
					$res = db("agent")->insert($data);
					if ($res) $this->success('添加成功', url('center/agent'));
				}else{
					$res = db("agent")->where("id='$id'")->update($data);
					if ($res) $this->success('修改成功', url('center/agent'));
				}
				$this->error('添加失败');
			}
			$where['id']=$id;
			$list = db("agent")->where($where)->find();
			$member= db("member")->where("type>0 and status=1")->order("id desc")->select();
			$this->assign('member',$member);
			$this->assign('data',$list);
			return $this->fetch();
		}
		/**setagentblack
		 *   搜索会员
		 */
		public function sou(){
			$key=input("key");
			$where['nickname|mobile']=array("like",'%'.$key.'%');
			$where['status']=1;
			$where['type']=1;
			$data=db("member")->where($where)->select();
			$str="";
			foreach($data as $v){
				$str.= "<option value='".$v['id']."'>".$v['nickname'].'-'.$v['mobile']."</option>";
			}
			echo $str;
		}
		/**
		 *   代理商黑名单
		 */
		public function setagentblack(){
			$id = input('id/d');
			$agent=db("agent")->where("id='$id'")->find();
			if($agent['status']==0){
				$data['status']=1;
			}else{
				$data['status']=0;
			}
			$res=db("agent")->where("id='$id'")->update($data);
			if($res){
				exit(json_encode(1));
			}else{
				exit(json_encode(0));
			}
		}
		/**
		 *   设置信用额
		 */
		public function credit(){
			$id = input('id/d');
			if(!$id){
				echo -1;exit;
			}
			$member=checkMember($id);
			if(!$member){
				echo -2;exit;
			}
			$data['credit'] = floatval(input('num'));
			$res=db("member")->where("id='$id'")->update($data);
			if($res===false) {
				echo -3;exit;
			}else{
				echo 1;
			}
		}
		/**
		 *   设置折扣
		 */
		public function discount(){
			$id = input('id/d');
			if(!$id){
				echo -1;exit;
			}
			$member=checkMember($id);
			if(!$member){
				echo -2;exit;
			}
			$data['discount'] = floatval(input('num'));
			$res=db("member")->where("id='$id'")->update($data);
			if($res===false) {
				echo -3;exit;
			}else{
				echo 1;
			}
		}

	/**
	 * 会员列表导出excel
	 */
	public function export($where)
	{

		$headArr = array("会员ID","会员昵称","手机号码","openid","注册日期","省份","城市","区/县","会员类型","信用额",'成交金额','成交订单数');
		$list = db('member')
				->where($where)
				->select();
		$order=db("order");
		$data=array();
		foreach($list as $k => $v){
			$data[$k][] = $v['id'];
			$data[$k][] = $v['nickname'];
			$data[$k][] = $v['mobile'];
			$data[$k][] = $v['wx_openid'];
			$data[$k][] = date("Y-m-d H:i",$v['create_time']);
			$data[$k][] = $v['province'];
			$data[$k][] = $v['city'];
			$data[$k][] = $v['county'];
			if($v['type']=='0'){
				$data[$k][] ='游客';
			}
			if($v['type']=='1'){
				$data[$k][] ='注册会员';
			}
			if($v['type']=='2'){
				$data[$k][] ='股东会员';
			}
			$data[$k][] = $v['credit'];
			$data[$k][] =$order->where("uid=".$v['id']." and status=3")->sum("price");
			$data[$k][] =$order->where("uid=".$v['id']." and status=3")->count();
		}
		$filename='客户列表';
		(new Export())->ImportExcel($filename,$headArr,$data);
	}

	protected function column_str($key)
	{
		$array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ');
		return $array[$key];
	}

	protected function column($key, $columnnum = 1)
	{
		return $this->column_str($key) . $columnnum;
	}
	/*
  * 设置黑名单
  */
	public function setblack(){
		$id = input('id/d');
		$member=db("member")->where("id='$id'")->find();
		if($member['status']==0){
			$data['status']=1;
		}else{
			$data['status']=0;
		}
		$res=db("member")->where("id='$id'")->update($data);
		if($res){
			exit(json_encode(1));
		}else{
			exit(json_encode(0));
		}
	}

		/*
    * 删除会员
    */
		public function del(){
			$id = input('id/d');
			$data['status'] =-1;
			$res=db("member")->where("id='$id'")->update($data);
			if($res){
				echo 1;exit;
			}else{
				echo -1;exit;
			}
		}
		public function main()
		{

			$years = array();
			$current_year = date('Y');
			$year = (empty(input('year')) ? $current_year : input('year'));
			$i = $current_year - 10;
			while ($i <= $current_year) {
				$years[] = array('data' => $i, 'selected' => $i == $year);
				++$i;
			}
			$months = array();
			$i = 1;
			$month = input('month');
			while ($i <= 12) {
				$months[] = array('data' => $i, 'selected' => $i == $month);
				++$i;
			}
			$list = array();
			$totalprice = 0;
			$costprice = 0;
			$db_order=db("order");
			$where['status']=3;
			$where['agentid']=input('agentid');
			if (!empty($year) && empty($month)) {
						foreach ($months as $k => $m) {
							$lastday = $this->get_last_day($year, $k + 1);
							$where['create_time']=array('between',[strtotime($year . '-' . $m['data'] . '-01 00:00:00'),strtotime($year . '-' . $m['data'] . '-' . $lastday . ' 23:59:59')]);
						//	var_dump($where);
							$price=$db_order->where($where)->sum('price');
							$costprice=$db_order->where($where)->sum('costprice');
							$dr = array(
									'data' => $m['data'].'月',
									'price' => floor($price*100)/100,
									'costprice' =>floor($costprice*100)/100,
							);
							$totalprice += $dr['price'];
							$costprice += $dr['costprice'];
							$list[] = $dr;
						}
					}
			if (!empty($year) && !empty($month)) {
					$lastday = $this->get_last_day($year, $month);

					$d = 1;
					for($d=1;$d<=$lastday;$d++){
						$where['create_time'] = array('between', [strtotime($year . '-' . $month . '-'. $d . '00:00:00'), strtotime($year . '-' . $month . '-' . $d . ' 23:59:59')]);
						//	var_dump($where);
						$price = $db_order->where($where)->sum('price');
						$costprice = $db_order->where($where)->sum('costprice');
						$dr = array(
								'data' =>$d . '日',
								'price' => floor($price * 100) / 100,
								'costprice' => floor($costprice * 100) / 100,
						);
						$totalprice += $dr['price'];
						$costprice += $dr['costprice'];
						$list[] = $dr;
					}
					/*while ($d <= $lastday) {
						$
					}*/

			}
			foreach ($list as $key => &$row) {
				$list[$key]['mao'] = number_format(($row['price']-$row['costprice']), 2);
				//$list[$key]['percent'] = number_format(($row['costprice'] / (empty($row['price']) ? 1 : $row['price'])) * 100, 2);
			}
			unset($row);
			if(input('export')){
				$this->exprotmain($list);exit;
			}
			$this->assign("months",$months);
			$this->assign("year",$year);
			$this->assign("month",$month);
			$this->assign("list",$list);
			$this->assign("years",$years);
			$this->assign("years",$years);
			$this->assign("totalprice",$totalprice);
			$this->assign("costprice",$costprice);
			$this->assign("maos",$totalprice-$costprice);
			return $this->fetch();
		}
		function get_last_day($year, $month)
		{
			return date('t', strtotime($year . '-' . $month . ' -1'));
		}
		public function exprotmain($data)
		{

			$headArr = array("月份","出货金额","入货金额","毛利");
			$filename='毛利列表'.date("Y-m-d H:i:s");
			(new Export())->ImportExcel($filename,$headArr,$data);
		}
}