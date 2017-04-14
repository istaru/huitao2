<?php
/**
 * 新手任务接口
 */
class NewbietaskController extends AppController {

	//{"user_id":""}
	/**
	 * [runCourse 看完教程]
	 */
	public function runCourse()
	{
		$this->checkuid();
		#判断是否已经奖励过
		$run = M()->query("SELECT id FROM ngw_uid_log WHERE uid = '{$this->dparam['user_id']}' and score_type = 5");

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

		M()->query("INSERT INTO ngw_share_log(type,uid,report_date,share_type,num_iid)Values({$this->dparam['type']},'{$this->dparam['user_id']}','{$this->dparam['report_date']}',{$this->dparam['share_type']},{$this->dparam['num_iid']})");

		#判断是否已经奖励过
		$run = M()->query("SELECT id FROM ngw_uid_log WHERE uid = '{$this->dparam['user_id']}' and score_type = 6");
		if(empty($run)){
			$this->unionAdd($this->dparam['user_id'],1,6,'首次分享商品奖励');

			$u_info = M()->query("select idfa,imei from ngw_uid where objectId = '{$this->dparam['user_id']}'",'single');

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
			M()->query("UPDATE ngw_uid set `price` = `price` + $price where `objectId` = '{$uid}'");
		} catch (Exception $e) {
			M()->rollback();
			info('数据处理失败',-1);
		}
		M()->commit();
		info('操作成功',1);
	}

	private function checkuid($uid = '') {
		$uid = $uid ? : empty($this->dparam['user_id']) ? info('请您赶快去注册登录吧', -1) : $this->dparam['user_id'];
		$uid_info = M('uid')->where(['objectId' => ['=', $uid]])->select('single');
		return $uid_info ?  : info('请您赶快去注册登录吧',-1);
	}
	//验证任务是否已经完成
	public function ckTask($key, $uid) {
		static $func = [
			1 => 'commoditySharingTask',
			2 => 'taobaoMandateTask',
			3 => 'aFriendInvitationTask',
			4 => 'oneSinglePurchaseTask',
			5 => 'friendsGetARedEnvelopeTask',
			6 => 'successInviteaFriendTask',
			7 => 'friendsAccumulatedTwoSingleTask',
			8 => 'friendConfirmTheDeliveryOfTwoTimesTask'
		];
		return !empty($func[$key]) ? call_user_func_array(['taskVerificationController', $func[$key]], [$uid]) : false;
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
						'task_id'	   => $data['task_id'],
						'uid'		   => $uid,
						'cost'		   => $data['price'],
						'score_info'   => $data['name'],
						'report_date'  => date('Y-m-d')
					]);
				}
			}
		} else {
			$data['status'] = 1;	//任务进行中状态
			return $this->userCompletedTasks($uid, $data);
		}
	}
	//查询新手任务进度
	public function queryTask() {
		$params = $this->dparam;
		$uid    = !empty($params['user_id']) ? $params['user_id'] : info('请您赶快去注册登录吧!', -1);
		//判断用户注册时间 是否可以做新手任务
		!strtotime(($this->checkuid($uid))['createdAt']) < 1487944802 OR info('2017-02-24之后注册的用户才可以参加新手任务', -1);
		//检测当前用户已完成的任务中是否还有未领取的奖励 如果有则不显示下个任务
		if($task = M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id IN( SELECT task_id FROM ngw_uid_bill_log WHERE status = 1 AND type = 2 AND uid = '{$uid}')", 'single')) {
			$task['status'] = 2;	//红包未领状态
			$this->userCompletedTasks($uid, $task);
		}
		//取出用户正在进行的任务
		$data = M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id NOT IN( SELECT task_id FROM ngw_task_log WHERE uid = '{$uid}') AND type = 1", 'single');
		//如果没有查到任务则表示该用户任务已经全部做完
		!empty($data) OR info('您已经做完了全部新手任务!', 1);
		//检测该任务用户是否已经完成了
		$this->ckNewUserMission($uid, $data);
		//如果能走到这一步表示用户完成的是那种没钱的任务 递归再检查下个任务
		$this->queryTask($uid);
	}
	public function userCompletedTasks($uid, $data) {
		$result   = M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id IN( SELECT task_id FROM ngw_task_log WHERE uid = '{$uid}') AND type = 1", 'all');
		$result[] = $data;
		foreach($result as $k => &$v) {
			$v['status'] = isset($v['status']) ? $v['status'] : 3;
			if(in_array($v['name'], $data) && 3 == $v['status'])
				unset($result[$k]);
		}
		info('ok', 1, array_values($result));
	}
}
