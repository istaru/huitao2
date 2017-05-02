<?php
class UserController extends AppController
{
	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [Info 个人中心数据]
	 */
	public function Info()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);

		//预估收入
		$sql = " SELECT  sum(price) predict FROM ngw_uid_log where status = 1 AND uid = '{$this->dparam['user_id']}' ";
		$predict = M()->query($sql);

		//用户信息
		$sql = " SELECT objectId uid,sfuid,nickname,head_img,price,pend,pnow,(price+pend+pnow) total FROM ngw_uid WHERE objectId = '{$this->dparam['user_id']}' ";
		$info = M()->query($sql);
		empty($info) && info('用户不存在',-1);
		$data = $predict['predict']==null?['predict'=>0]+$info:$predict+$info;
		info(['msg'=>'请求成功!','status'=>1,'data'=>$data]);
	}


	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [incomesList 收入明细]
	 */
	public function incomesLog()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);
		$sql = "SELECT l.createdAt as date_time,u.nickname as friend_name,l.price,l.status,l.score_info,l.score_type FROM ngw_income_log l JOIN ngw_uid u ON l.uid = u.objectId WHERE uid = '{$this->dparam['user_id']}'";

		$uidLog_list = M()->query($sql,'all');
		foreach ($uidLog_list as $k => &$v) {
			if($v['status'] == 3) $v['price'] = $v['price'] * -1;
			if($v['score_type'] > 4) unset($v['friend_name']);
			$v['date_time'] = substr($v['date_time'], 0, -3);
			unset($v['status']);
		}
		info('请求成功',1,$uidLog_list);
	}


	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [pnowLog 提现明细]
	 */
	public function pnowLog()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);

		$sql = " SELECT price,status,updatedAt date_time,if(status<4,errmsg,( CASE status WHEN 4 THEN duiba_success ELSE duiba_end_errmsg END)) msg FROM ngw_pnow WHERE uid = '{$this->dparam['user_id']}'";

		info('请求成功',1,M()->query($sql,'all'));
	}
	/**
	 * [checkbindMasters 好友绑定验证规则]
	 * @param  [type] $uid   [徒弟uid]
	 * @param  [type] $sfuid [师傅uid]
	 * @return [Array || String] [array('sfNname' => '师傅名称', 'name' => '徒弟名称') || 规则未通过的原因提示]
	 */
	public function checkbindMasters($uid, $sfuid) {
		try {
			//验证用户是否已经注册登录
			if(!$user = userBehaviorVerificationController::userRegistration($uid)) E('您赶紧去注册登录吧');
			//验证用户是否已经绑定过师傅了
			if(!empty($user['sfuid'])) E('您已经填写过邀请人了');
			//验证要绑定的好友账号是否存在
			if(!$sf = userBehaviorVerificationController::userRegistration($sfuid)) E('您填写的好友不存在');
			if($sf['objectId'] == $user['objectId']) E('不允许绑定自己');
			//验证该用户是否已经淘宝授权过 且该淘宝账号只被授权过一次
			if(!$taobaoInfo = userBehaviorVerificationController::queryTaoBaoAuthId($user['taobao_id'])) E('请您先淘宝授权');
			if(count($taobaoInfo) > 1) E('您已经不是新用户啦');
			//该用户设备号只有一次记录的才允许绑定好友
			count(userBehaviorVerificationController::queryUserDeviceInformation([
				'uuid' => $user['uuid'],
				'bdid' => $user['bdid'],
				'idfa' => $user['idfa'],
				'imei' => $user['imei']
			])) <= 1 OR E('您已经不是新用户啦');
			$did_log = userBehaviorVerificationController::queryUserDeviceInformation([
				'uuid' => $user['uuid'],
				'bdid' => $user['bdid'],
				'idfa' => $user['idfa'],
				'imei' => $user['imei']
			]);
			foreach($did_log as $v) {
				foreach($did_log as $value) {
					if($value != $v)
						E('您已经不是新用户啦');
				}
			}
			//验证用户要绑定的好友是否已经淘宝授权 并且双方淘宝授权账号不一样
			if(empty($sf['taobao_id'])) E('您的好友可能还未允许淘宝授权暂时无法绑定好友');
			if($sf['taobao_id'] == $user['taobao_id']) E('淘宝授权账号重复暂时无法绑定好友');

			//杜绝出现互绑情况 比如 1的徒弟是2  1的师傅是2
			if($n = M('uid_log')->field('score_source')->where(['uid' => ['=', $uid], 'score_type' => ['=', 2],], ['and'])->select())
				!in_array($sfuid, array_column($n,'score_source')) OR E('他已经是您的好友了呀');
		} catch (Exception $e) {
			return $e->getMessage();
		}
		return [
			'sfNname' => $sf['nickname'],
			'name'	  => $user['nickname']
		];
	}
	/**
	 * [bindMasters 绑定好友关系]
	 * @param  boolean $type     [是否开启信息提示]
	 * @param  string  $objectId [用户uid]
	 * @param  string  $sfuid    [师傅uid]
	 * @return [type]            [description]
	 */
	public function bindMasters($type = true, $objectId = 'E0iIAA2z69', $sfuid = '0wG5FIQQMi') {
		try {
			M()->startTrans();
			//好友绑定规则验证
			$value = $this->checkbindMasters($objectId, $sfuid);
			is_array($value) ? : E($value);
			//如果是首次邀请徒弟则奖励2元 其余不奖励
			list($price, $scoreInfo) = !M('uid_log')->where(['uid' => ['=', $sfuid], 'score_type' => ['=', 2]],['and'])->field('id')->count() ? [2, '首次绑定好友奖励2元'] : [0, '绑定好友'];
			M('uid_log')->add([
				'uid' 			=> $sfuid,
				'score_source'	=> $objectId,
				'score_info'	=> $scoreInfo,
				'price'			=> $price,
				'score_type'	=> 2,
				'status'		=> 2,
			]) or E('绑定失败');
			M()->exec("UPDATE ngw_uid SET price = price + {$price},invitation_count = invitation_count + 1 WHERE objectId='{$sfuid}'") OR E('绑定失败');
			M()->exec("UPDATE ngw_uid SET sfuid = '{$sfuid}' WHERE objectId='{$objectId}'") OR E('绑定失败');
			//给用户发送消息通知
			(new MessageModel)->batchAddMsg([
				[
					'uid'		 => $sfuid,
					'content'	 => $value['name'].'绑定了您为好友'
				], [
					'uid'		=> $objectId,
					'content'	=> '您成功绑定了'.$value['sfNname'].'为好友'
				],
			]) OR E('绑定失败');
		} catch(Exception $e) {
			M()->rollback();
			return $type ? info($e->getMessage(),-1) : false;
		}
		M()->commit();
		return $type ? info('绑定成功') : true;
	}


	//{"user_id":"ZRzjAdppve"}
	/**
	 * [message 消息]
	 */
	public function message()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);
		$msg_where = " uid = '{$this->dparam['user_id']}' and type = 1 ";
		$msg_field = " createdAt as date_time , content as msg";
		$msg_info = A('Message:getMsg',[$msg_where,$msg_field]);
		info('请求成功',1,$msg_info);
	}


	//{"user_id":"ZRzjAdppve"}
	/**
	 * [redMessage description]
	 */
	public function redMessage()
	{
		$rath = 1;
		empty($this->dparam['user_id']) && info('数据不完整',-1);
		$sql = " select a.createdAt date_time , a.content msg , a.bid ,a.status , (b.cost * {$rath}) price from ngw_message a join ngw_uid_bill_log b on a.bid = b.id where  a.uid = '{$this->dparam['user_id']}' and a.type = 2 order by a.createdAt DESC limit 100 ";
		$info = M()->query($sql,'all');

		$msg_info = ['untake'=>[],'token'=>[],'all'=>$info];

		foreach ($info as $k => $v) {

			if($v['status'] == 1){
				$msg_info['untake'][] = $v;
			}
			else if($v['status'] == 2){
				$msg_info['token'][] = $v;
			}

		}

		info('请求成功',1,$msg_info);

	}

	/**
	 * [uid_info 用户头像和昵称]
	 */
	public function uid_info()
	{
		$uid_info =  A('Uid:getInfo',["objectId = '{$_POST['user_id']}'",'nickname,head_img','single']);
		info('请求成功',1,$uid_info);
	}

	/**
	 * [returnBindSfuid 返回邀请页建立好友关系的师傅]
	 */
	public function returnBindSfuid($phone)
	{
		$where = [
			'status' => ['=', 1],
			'phone'	 => ['=', $phone],
			['and'],
		];
		$friend_exisit = M('friend_log')->where($where)->select('single');
		return $friend_exisit;
	}

	/**
	 * [addFrieldLog 邀请页建立好友关系以及分享app]
	 */
	public function addFrieldLog() {
		$params = $_REQUEST;
		if(!$params['phone'] || !$params['user_id']) info('数据不完整',-1);
	    if(!preg_match("/^1[34578]\d{9}$/",$params['phone'])) info('非法手机号',-1);
		(UserRecordController::getObj()) -> shareRecord($params['user_id'],null,3,0); //1分享商品
	    $exisit_tb = M()->query("select * from ngw_taobao_log where uid = '{$params['user_id']}'",'single');
	    if(empty($exisit_tb)) info('您的邀请人还未进行淘宝授权!',-1);
		$is_new_user = M()->query("select id from ngw_uid where phone ='{$params['phone']}' ",'single',true);
		if(!empty($is_new_user)) info('亲,你已经是惠淘会员了!',-1);
		$friend_exisit = M()->query("select sfuid from ngw_friend_log where phone = '{$params['phone']}' ",'single',true);
		if(!empty($friend_exisit)) info('亲,您已经被邀请过!',-1);
		$add_data = ['phone'=>$params['phone'],'sfuid'=>$params['user_id']];
		if(M('friend_log')->add($add_data,true)) info('您已成功被邀请!',1);
	}

	/**
	 * [addPushAssoc 建议友盟device_token和uid对应关系]
	 */
	private function addPushAssoc($uid,$device_token,$type)
	{
		if(!$type) $type = 2;
		$id = M('push_assoc')->add(['uid'=>$uid,'device_token'=>$device_token,'type'=>$type]);
		return $id;
	}

	/**
	 * [addPushAssocForIOS IOS邀请另外接口注册时可能获取不到token]
	 */
	public function addPushAssocForApp()
	{
		if(!$this->dparam['type']) $this->dparam['type'] = 2;
		if(!empty($this->dparam['user_id']) && !empty($this->dparam['device_token']) && ($this->dparam['type'])){
			$id = $this->addPushAssoc($this->dparam['user_id'],$this->dparam['device_token'],$this->dparam['type']);
			if($id){
				info('操作成功',1);
			}else{
				info('已经存在',-1);
			}
		}else{
			info('数据不完整',-1);
		}
	}


	//{"user_id":"123","bid":["123","321","222"]}
	/**
	 * [getReward 拆红包(购买)]
	 */
	public function getReward()
	{
		if(empty($this->dparam['bid']) || empty($this->dparam['user_id']))
			info(-1,'数据不完整');
		$bill_ids = implode(',',$this->dparam['bid']);
		//取出用户拆红包对应的所有账单
		$sql = "select * from ngw_uid_bill_log where type = 1 and uid = '{$this->dparam['user_id']}' and id in ($bill_ids)";
		$data = M()->query($sql,'all');
		foreach ($data as $v){
			(ShopincomeController::getObj())->getReward($v);
		}
		info('红包领取,请查收!',1);

	}


	//{"user_id":"123","task_id":["123","222"]}
	/**
	 * [getReward 拆红包(任务)]
	 */
	public function getRewardTask() {
		$params = $this->dparam;
		if(empty($params['task_id']) || empty($params['user_id']))
			info(-1,'缺少参数');
		$data = M()->query("select * from ngw_uid_bill_log where type = 2 and uid = '{$params['user_id']}' and task_id = {$params['task_id']}",'all');
		if(!empty($data)) {
			foreach ($data as $v)
				(TaskincomeController::getObj())->getRewardForTask($v);
		} else info('参数异常',-1);
		info('领取成功', 1);
	}

}
