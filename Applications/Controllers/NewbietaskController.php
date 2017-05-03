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
		if(empty($uid) && empty($this->dparam['user_id']))
			info('请您赶快去注册登录吧!');
		$uid = $uid ? : $this->dparam['user_id'];
		$uid_info = M('uid')->where(['objectId' => ['=', $uid]])->select('single');
		return $uid_info ?  : info('请您赶快去注册登录吧',-1);
	}
	//验证任务是否已经完成
	public function ckTask($key, $uid) {
		static $func = [
			1 => 'commoditySharingTask',
			2 => 'verifyUserTaobaoLicense',
			3 => 'aFriendInvitationTask',
			4 => 'oneSinglePurchaseTask',
			5 => 'friendsGetARedEnvelopeTask',
			6 => 'successInviteaFriendTask',
			7 => 'friendsAccumulatedTwoSingleTask',
			8 => 'friendConfirmTheDeliveryOfTwoTimesTask'
		];
		return !empty($func[$key]) ? call_user_func_array(['userBehaviorVerificationController', $func[$key]], [$uid]) : false;
	}
	public function ckNewUserMission($uid, $data) {
		if($this->ckTask($data['task_id'], $uid)) {
			//任务表并不存在这一条记录的时候 添加一条记录
			if(empty(M('task_log')->where("uid = '{$uid}' AND task_id = {$data['task_id']}")->select('single'))) {
				$data['uid'] = $uid;
				//添加该任务日志记录
				M('task_log')->add($data);
				//不论该任务有没有金额奖励 都存uid_bill_log 记录
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
		} else {
			$data['status'] = 1;	//任务进行中状态
			info('任务进行中', 1, $this->userCompletedTasks($uid, $data));
		}
	}
	//查询新手任务进度
	public function queryTask() {
		$params = $this->dparam;
		$uid    = !empty($params['user_id']) ? $params['user_id'] : info('请您赶快去注册登录吧!', -1);
		do {
			if($task = M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id IN( SELECT task_id FROM ngw_uid_bill_log WHERE status = 1 AND type = 2 AND uid = '{$uid}')", 'single')) {
				$task['status'] = 2;	//红包未领状态
				info('立即领取奖励', 1, $this->userCompletedTasks($uid, $task));
			}
			//取出用户正在进行的任务
			$data = userBehaviorVerificationController::userOngoingTask($uid);
			//如果没有查到任务则表示该用户任务已经全部做完
			if(empty($data)) {
				info('ok', 1, $this->userCompletedTasks($uid, [
					'step' => '', 'task_img' => 'resource/img/task/img_task_09.png', 'name' => '您已完成新手任务', 'status' => 4
				]));
			}
			//检测该任务用户是否已经完成了
			$this->ckNewUserMission($uid, $data);
		} while (true);
	}
	public function userCompletedTasks($uid, $data = []) {
		$result = userBehaviorVerificationController::userCompletedTask($uid);
		$result[] = $data;
		foreach($result as $k => &$v) {
		    $v['task_img'] = RES_SITE.$v['task_img'];
		    $step = explode(',', $v['step']);
		    $explain = '';
		    foreach($step as $val) {
		        $explain .= RES_SITE.$val.',';
		    }
		    $v['step'] = rtrim($explain, ',');
		    $v['status'] = isset($v['status']) ? $v['status'] : 3;
		    if(in_array($v['name'], $data) && 3 == $v['status']) {
		    	unset($result[$k]);
		    }
		}
		return array_values($result);
	}
}
