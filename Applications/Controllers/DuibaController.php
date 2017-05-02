<?php
/*
兑吧提现控制器
 */

class DuibaController extends AppController
{
	//http://item.mssoft.info/shopping/duiba/exchange
	//{"user_id":"Nuwd8XEsBs","price":"1","alipay_num":"kkk_se7en@qq.com","alipay_name":"张浩"}

	/**
	 * [exchange 提现申请(申请入库,收集兑吧接口需要的字段)]
	 */
	public function exchange()
	{
		//uid预扣款
		if(!$this->dparam['user_id'] || !$this->dparam['price']) info('请求信息不完整',-1);

		$uid_where = "objectId = '{$this->dparam['user_id']}'";

		$uid_info =  A('Uid:getInfo',[$uid_where,'*','single']);

		if(empty($uid_info)) info('用户不存在!',-1);
		// //防作弊验证
		// if(!$this->antiCheating())
		// 	info('去找客服提现',-1);
		//获取周提现总和
		$p_info = M()->query("select sum(price) as totle from ngw_pnow where uid = '{$this->dparam['user_id']}' and createdAt >date_sub(curdate(),interval WEEKDAY(curdate()) day) and status in (1,2,4)");
		if(!empty($p_info) && $p_info['totle'] >= 500)  info('超过本周提现限额请您去找客服进行提现',-1);

		$ck = M()->query("select id  from ngw_pnow where uid = '{$this->dparam['user_id']}' and status  = 1");

		if(!empty($ck)) info('您有笔提现申请还在审核中',-1);


		if($uid_info['price'] < $this->dparam['price']) info('余额不足',-1);
		if(empty($this->dparam['alipay_num']) || empty($this->dparam['alipay_name']))  info('支付宝信息不完整',-1);


		//更新用户金额
		$uid_data = [
			'price' => $uid_info['price'] - $this->dparam['price'],	//余额
			'pnow' => $uid_info['pnow'] + $this->dparam['price'],	//在提现金额
			'pnowcount' => $uid_info['pnowcount'] + 1,	//提现次数
			'pnowtime' => time(),	//本次提现时间
			'alipay' => $this->dparam['alipay_num'],
			'alipay_name' => $this->dparam['alipay_name'],
		];
		$pnow_data = [
			'uid'=>$this->dparam['user_id'],
			'status'=>1,
			'did'=>$uid_info['did'],
			'price'=>$this->dparam['price'],
			'alipay'=>$this->dparam['alipay_num'],
			'alipay_name'=>$this->dparam['alipay_name'],
		];
		M()->startTrans();
		try {
			if(!A('Uid:updateUid',[$uid_where,$uid_data])) E('申请提现失败1');
			if(!A('Pnow:addPnow',[$pnow_data])) E('申请提现失败2');
		} catch (Exception $e) {
			M()->rollback();
			info($e->getMessage(),-1);
		}
		M()->commit();
		info('申请提现成功,请耐心等待',1);

	}



	//http://item.mssoft.info/shopping/duiba/zhida
	//直达兑吧兑换页(兑吧调我们)
	public function zhida()
	{
		if(empty($_POST['uid']) || empty($_POST['pid']) || empty($_POST['price']) || empty($_POST['alipay']) || empty($_POST['alipay_name'])) info('参数不全',-1);

		//拼接参数向兑吧请求
		$arr = [
			'uid'=>$_POST['uid'].'_'.$_POST['pid'],
			'credits'=>$_POST['price'],
			'alipay'=>$_POST['alipay'],
			'realname'=>$_POST['alipay_name'],
			'appKey'=>parent::DUIBA_KEY,
			'timestamp'=>round(microtime(true),3)*1000,
			'appSecret'=>parent::DUIBA_SECRET,

		];
		$arr['sign'] = $this->sign($arr);
		unset($arr['appSecret']);
		$url = parent::DUIBA_AUTO_URL;
		$url.="uid=".$arr['uid']."&credits=".$arr['credits']."&appKey=".$arr['appKey']."&sign=".$arr['sign']."&timestamp=".$arr['timestamp'].'&alipay='.$arr['alipay'].'&realname='.$arr['realname'];
		info(['msg'=>'ok','status'=>1,'url'=>$url]);

	}




	//http://localhost/shopping/duiba/submit?uid=dwqe123_1&credits=50&orderNum=2&timestamp=3&type=4
	//积分消耗(回调)
	public function submit()
	{
		$url =  'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		// M('calltest')->add(['content'=>$url]);
		$info = explode('_',$_GET['uid']);	// [0]uid , [1]pnow objectid
		$u_where = ['objectId'=>['=',$info[0]]];
		$u_info = A('Uid:getInfo',[$u_where,'*']);	//获取用户信息
		$p_where = ['id'=>['=',$info[1]]];
		$p_info = A('Pnow:getPnowInfo',[$p_where,'*','single']); //获取提现信息

		if(empty($p_info))
			$this->rinfo(['status'=>'fail','errorMessage'=>'提现申请不存在','credits'=>$u_info['price']]);

		// echo (int)$p_info['price'] .'----'. $_GET['credits'];die;
		if((int)$p_info['price'] != $_GET['credits'])
			$this->rinfo(['status'=>'fail','errorMessage'=>'申请提现金额不正确','credits'=>$u_info['price']]);

		//只消费一次
		if($p_info['duiba_stime'])
			$this->rinfo(['status'=>'fail','errorMessage'=>'submit积分已经消费过','credits'=>$u_info['price']]);

		$p_data = [
			'price'=>$_GET['credits'],
			'duiba_order'=>$_GET['orderNum'],
			'duiba_stime'=>$_GET['timestamp'],
			'paychoose'=>$_GET['type'],
			'status'=>2,
			'info'=>$url,
		];

		$p_up = A('Pnow:updatePnow',[$p_where,$p_data]);

		if(!empty($p_up))
		{
			$this->rinfo(['status'=>'ok','errorMessage'=>'','bizId'=>$info[1],'credits'=>(int)$u_info['price']]);
		}else{
			$this->rinfo(['status'=>'fail','errorMessage'=>'请求失败,数据异常','credits'=>(int)$u_info['price']]);
		}


	}


	//通知结果(兑吧调我们)
	public function feedback()
	{
		$url =  'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		// M('calltest')->add(['content'=>$url]);

		$p_where = " duiba_order = '{$_GET['orderNum']}' ";

		$p_info = A('Pnow:getPnowInfo',[$p_where,'*','single']); //获取提现信息
		// D($p_where);
		$uid_where = "objectId = '{$p_info['uid']}'";
		$uid_info =  A('Uid:getInfo',[$uid_where,'*','single']);
		if(empty($p_info['duiba_etime']))
		{
			if($_GET['success'] == 'true')
			{
				$p_data = [
					'duiba_success'=>'充值成功',
					'status'=>4,
					'duiba_etime'=>$_GET['timestamp'],
					'info'=>$url,
				];

				if($uid_info['pnow'] < $p_info['price'])
				{
					echo 'fail,数据有误';
				}
				$uid_data = [
					'pnow' => $uid_info['pnow'] - $p_info['price'],	//在提现金额
					'pend' => $uid_info['pend'] + $p_info['price'],	//提现过的总额
					'pendcount' => $uid_info['pendcount'] +1,	//完成提现次数
					'pendtime' => time(),	//本次提现完成时间
				];
			}else{
				$uid_data = [
					'pnow' => $uid_info['pnow'] - $p_info['price'],	//在提现金额
					'price' => $uid_info['price'] + $p_info['price'],	//提现过的总额
				];
				$p_data = [
					'status'=>5,
					'duiba_end_errmsg'=>$_GET['errorMessage'],
					'duiba_etime'=>$_GET['timestamp'],
					'info'=>$url,
				];
			}
			// D($p_where);
			// D($p_data);
			// D($uid_where);
			// D($uid_data);die;
			M()->startTrans();
			try {
				if(!A('Pnow:updatePnow',[$p_where,$p_data])) E('修改提现表失败');
				if(!A('Uid:updateUid',[$uid_where,$uid_data])) E('申请提现失败1');
			} catch (Exception $e) {
				M()->rollback();
				info($e->getMessage(),-1);
			}
			M()->commit();
			echo 'ok';
		}else{
			echo 'ok,已记录过了';
		}


	}


	//生成兑吧签名规则
	private function sign($arr)
	{
		ksort($arr);
		$string = '';

		while (list($key, $val) = each($arr))
		{
		  $string = $string . $val ;
		}
		return md5($string);
	}

	/**
	 * [antiCheating 防作弊处理]
	 * 前7天共提现金额不超过500 每位徒弟带来的收入不超过50  否则--客服提现
	 */
	public function antiCheating($uid)
	{
		$week1 = date('Y-m-d H:i:s',strtotime('-1 week last monday')); //上周1 00:00:00
		$week7 = date('Y-m-d H:i:s',strtotime('+1 day last sunday')); //上周日日期+1 00:00:00
		/**
		 * 上一周共提现金额不超过500
		 */
		$week = M('pnow')->where("createdAt BETWEEN '{$week1}' AND '{$week7}' AND duiba_success = '充值成功'")->field('sum(price) as price')->select('single');
		if(!empty($week['price']) && $week['price'] >= 500)
			return;
		/**
		 * 判断该用户徒弟上一周给他带来的总收入平均是否超过50元
		 */
		$apprentice = M('uid_log')->where("createdAt BETWEEN '{$week1}' AND '{$week7}' AND status=2")->field('avg(price) as price')->select('single');
		if(!empty($apprentice['price']) && $apprentice['price'] >= 50)
			return;
		return true;
		// $p_info = M()->query("select sum(price) as totle from ngw_pnow where uid = '{$uid}' and createdAt > DATE_SUB(curdate(),date_format(curdate(),'%w')-1) and duiba_success = '充值成功'");

		// if($p_info['totle'] >= 500)  info('超过本周提现限额',-1);
	}

	public function rinfo($data)
	{
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
		exit;
	}
}