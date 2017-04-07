<?php
/**
 * 新手任务接口
 */
class NewbietaskController extends AppController
{

	//{"user_id":""}
	/**
	 * [runCourse 看完教程]
	 */
	public function runCourse()
	{
		$this->checkuid();
		#判断是否已经奖励过
		$run = M()->query("SELECT id FROM gw_uid_log WHERE uid = '{$this->dparam['user_id']}' and score_type = 5");

		if(empty($run))
			$this->unionAdd($this->dparam['user_id'],1,5,'完成新手任务');
		else
			info('用户已经获得过该奖励',-1);
	}

	//{"user_id":""}
	/**
	 * [shareGoods 分享商品]
	 */
	public function shareGoods()
	{
		$this->checkuid();
		$this->dparam['uid'] = $this->dparam['user_id'];
		$this->dparam['report_date'] = date('Y-m-d');
		if($this->dparam['num_iid'] == '0'){
			$this->dparam['share_type'] = 0;
		}else{
			$this->dparam['share_type'] = 1;
		}
		if(empty($this->dparam['type'])) $this->dparam['type'] = 0;
		// echo M('share_log')->add($this->dparam,false);die;

		M()->query("INSERT INTO gw_share_log(type,uid,report_date,share_type,num_iid)Values({$this->dparam['type']},'{$this->dparam['user_id']}','{$this->dparam['report_date']}',{$this->dparam['share_type']},{$this->dparam['num_iid']})");

		#判断是否已经奖励过
		$run = M()->query("SELECT id FROM gw_uid_log WHERE uid = '{$this->dparam['user_id']}' and score_type = 6");
		if(empty($run)){
			$this->unionAdd($this->dparam['user_id'],1,6,'首次分享商品奖励');

			$u_info = M()->query("select idfa,imei from gw_uid where objectId = '{$this->dparam['user_id']}'",'single');

			// $cla = new HeadacheController;
			// if($this->dparam['type'] === '1'){
			// 	// $cla->registerActivation(['imei' => $u_info['imei'], 'uid' => $objectId]);
			// }else{
			// 	$cla->registerActivation(['idfa' => $u_info['idfa'], 'uid' => $this->dparam['user_id']]);
			// }

		}else{
			info('用户已经获得过该奖励',-1);
		}
	}

	/**
	 * [add 增加操作]
	 * @param [type]  $uid        [用户id]
	 * @param [type]  $price      [增加的金额]
	 * @param [type]  $score_type [收入类型]
	 * @param [type]  $score_info [description]
	 * @param integer $status     [预估/余额]
	 */
	private function unionAdd($uid,$price,$score_type,$score_info,$status=2)
	{
		try {
			M()->startTrans();
			M('uid_log')->add(['uid'=>$uid,'score_type'=>$score_type,'score_info'=>$score_info,'price'=>$price,'status'=>$status],true);
			M('message')->add(['uid'=>$uid,'content'=>$score_info],true);
			M('income_log')->add(['uid'=>$uid,'score_type'=>$score_type,'score_info'=>$score_info,'price'=>$price,'status'=>$status],true);
			M()->query("UPDATE gw_uid set `price` = `price` + $price where `objectId` = '{$uid}'");
		} catch (Exception $e) {
			M()->rollback();
			info('数据处理失败',-1);
		}
		M()->commit();
		info('操作成功',1);
	}

	private function checkuid()
	{
		if(empty($this->dparam['user_id'])) info('用户uid不正确',-1);
		$uid_info = M()->query("SELECT createdAt FROM gw_uid where objectId = '{$this->dparam['user_id']}'", 'single');
		if(empty($uid_info)) info('用户不存在',-1);
		return $uid_info;
	}
	//验证任务是否已经完成
	public function ckTask($key, $uid) {
		$data = [
			//完成一次商品分享
			1 => M('share_log')->where("uid = '{$uid}' AND share_type = 1")->select('single'),
			//绑定淘宝账号
			2 => M('taobao_log')->where("uid = '{$uid}'")->select('single'),
			//完成一次好友邀请
			3 => M('friend_log')->where("sfuid = '{$uid}'")->select('single'),
			//完成一次下单
			4 => M('shopping_log')->where("uid = '{$uid}'")->select('single'),
			//获得一个红包(返利类型商品确认后)
			5 => false,
			//成功邀请一名好友
			6 => M('uid_log')->where("uid = '{$uid}' AND score_type = 2")->select('single'),
			//所有好友累计两次下单
			7 => (M()->query("SELECT count(b.uid) num FROM( SELECT score_source FROM gw_uid_log WHERE score_type = 2 AND uid = '{$uid}') a JOIN(SELECT uid , order_id FROM gw_order) b ON b.uid = a.score_source"))['num'] > 1 ? 1 : 0,
			//所有好友累计两次确认收货
			8 => (M()->query("SELECT count(c.id) num FROM( SELECT score_source FROM gw_uid_log WHERE score_type = 2 AND uid = '{$uid}') a JOIN(SELECT uid , order_id FROM gw_order) b ON b.uid = a.score_source JOIN( SELECT id , order_id FROM gw_order_status WHERE status = 3) c ON c.order_id = b.order_id"))['num'] > 1 ? 1 : 0
		];
		return isset($data[$key]) ? $data[$key] : '';
	}
	public function ckNewUserMission($uid, $data) {
		if($this->ckTask($data['task_id'], $uid)) {
			//任务表并不存在这一条记录的时候 添加一条记录
			if(empty(M('task_log')->where("uid = '{$uid}' AND task_id = {$data['task_id']}")->select('single'))) {
				$data['uid'] = $uid;
				//添加该任务日志记录
				M('task_log')->add($data);
				//当任务奖励金额大于0的时候 给用户钱并存uid_bill_log 记录 然后info给前端 红包未领的状态
				if($data['price'] > 0) {
					M('uid_bill_log')->add([
						'type'		   => 2,
						'score_type'   => 10,
						'order_id'	   => $data['task_id'],
						'uid'		   => $uid,
						'cost'		   => $data['price'],
						'score_info'   => $data['name'],
						'report_date'  => date('Y-m-d')
					]);
					info('立即领取', 1, [$data]);
				}
			}
		} else { //进行中
			info('正在进行中', 1, [$data]);
		}
	}
	//查询新手任务进度
	public function queryTask() {
		$params = $_POST;
		$uid = !empty($params['user_id']) ? $params['user_id'] : info('请您赶快去注册登录吧!', -1);
		//判断用户注册时间 是否可以做新手任务
		if($user = M('uid')->where("objectId = '{$uid}'")->field('createdAt')->select('single')) {
			!strtotime($user['createdAt']) < 1487944802 or info('2017-02-24之后注册的用户才可以参加新手任务', -1);
			$field = 'id task_id,name,introduce,price,createdAt';
			//检测当前用户已完成的任务中是否还有未领取的奖励 如果有则不显示下个任务
			if($task = M()->query("SELECT {$field} FROM gw_task WHERE id IN( SELECT order_id FROM gw_uid_bill_log WHERE status = 1 AND type = 2 AND uid = '{$uid}')", 'single'))
				info('立即领取', 1, [$task]);
			//取出用户正在进行的任务
			$data = M()->query("SELECT {$field} FROM gw_task WHERE id NOT IN( SELECT task_id FROM gw_task_log WHERE uid = '{$uid}') AND type = 1 LIMIT 1", 'single');
			//如果没有查到任务则表示该用户任务已经全部做完
			!empty($data) or info('您已经做完了全部新手任务!', 1);
			//检测该任务用户是否已经完成了
			$this->ckNewUserMission($uid, $data);
			//如果能走到这一步表示用户完成的是那种没钱的任务 递归再检查下个任务
			$this->queryTask($uid);
		}
		info('网络异常!');
	}
}