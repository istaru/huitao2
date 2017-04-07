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
		$sql = " SELECT  sum(price) predict FROM gw_uid_log where status = 1 AND uid = '{$this->dparam['user_id']}' ";
		$predict = M()->query($sql);

		//用户信息
		$sql = " SELECT objectId uid,sfuid,nickname,head_img,price,pend,pnow,(price+pend+pnow) total FROM gw_uid WHERE objectId = '{$this->dparam['user_id']}' ";
		$info = M()->query($sql);
		empty($info) && info('用户不存在',-1);

		info(['msg'=>'请求成功!','status'=>1,'predict'=>(int)$predict['predict']]+$info);
	}


	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [pnowLog 提现明细]
	 */
	public function pnowLog()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);

		$sql = " SELECT price,status,updatedAt date_time,if(status<4,errmsg,(CASE status WHEN 5 THEN duiba_success WHEN 6 THEN duiba_end_errmsg ELSE '请耐心等待' END)) msg FROM gw_pnow WHERE uid = '{$this->dparam['user_id']}'";

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
			$uid != $sfuid OR E('不允许绑定自己');
			//验证该用户是否存在表中
			$user = M('uid')->field('sfuid,nickname,taobao_id')->where(['objectId'=> ['=',$uid]])->select('single');
			empty($user) ? E('您赶紧去注册登录吧') : empty($user['sfuid']) ? : E('您已经填写过邀请人了');
			//验证该用户是否已经淘宝授权过 且该淘宝账号只被授权过一次
			$taobaoInfo = M('taobao_log')->where(['taobao_id' => ['=', $user['taobao_id']]])->select('all');
			count($taobaoInfo) > 1 ? E('您已经不是新用户啦') : !count($taobaoInfo) < 1 ? : E('请您先淘宝授权');
			//该用户设备号只有一次记录的才允许绑定好友
			count((new DidModel)->getUserDid($uid)) == 1 OR E('您已经不是新用户啦');
			//徒弟 师傅如果是一个淘宝授权账号  禁止绑定关系
			$shiFu = M('uid')->where(['objectId' => ['=', $sfuid], 'Invitation_code' => ['=', $sfuid]], ['OR'])->select('single') OR E('您填写的好友不存在');
			empty($shiFu['taobao_id']) ? E('您的好友可能还未允许淘宝授权暂时无法绑定好友') : $shiFu['taobao_id'] != $user['taobao_id'] OR E('淘宝授权账号重复暂时无法绑定好友');
			//杜绝出现互绑情况 比如 1的徒弟是2  1的师傅是2
			if($n = M('uid_log')->field('score_source')->where(['uid' => ['=', $uid], 'score_type' => ['=', 2],], ['and'])->select())
				!in_array($sfuid, array_column($n,'score_source')) OR E('他已经是您的好友了呀');
		} catch (Exception $e) {
			return $e->getMessage();
		}
		return [
			'sfNname' => $shiFu['nickname'],
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
	public function bindMasters($type = true, $objectId = '123', $sfuid = 'name') {
		try {
			M()->startTrans();
			//好友绑定规则验证
			in_array($value = $this->checkbindMasters($objectId, $sfuid)) ? : E($value);
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
			M()->exec("UPDATE gw_uid SET price = price + {$price},invitation_count = invitation_count + 1 WHERE objectId='{$sfuid}'") OR E('绑定失败');
			M()->exec("UPDATE gw_uid SET sfuid = '{$sfuid}' WHERE objectId='{$objectId}'") OR E('绑定失败');
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


	/**
	 * [redMessage description]
	 */
	public function redMessage()
	{
		empty($this->dparam['user_id']) && info('数据不完整',-1);
		$sql = " select createdAt as date_time , content as msg , bid ,status from gw_message where  uid = '{$this->dparam['user_id']}' and type = 2 order by createdAt DESC limit 100 ";
		$info = M()->query($sql,'all');

		$msg_info = ['untake'=>[],'token'=>[],'all'=>$info];

		foreach ($info as $k => $v) {

			if($v['status'] == 1){
				unset($v['status']);
				$msg_info['untake'][] = $v;
			}
			else if($v['status'] == 2){
				unset($v['status']);
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
	 * [addFrieldLog 邀请页建立好友关系]
	 */
	public function addFrieldLog()
	{
		if(!$_REQUEST['phone'] || !$_REQUEST['user_id']) info('数据不完整',-1);
	    if(!preg_match("/^1[34578]\d{9}$/",$_REQUEST['phone'])) info('非法手机号',-1);
	    $exisit_tb = M()->query("select * from gw_taobao_log where uid = '{$_REQUEST['user_id']}'",'single');
	    if(empty($exisit_tb)) info('您的邀请人还未进行淘宝授权!',-1);
		$is_new_user = M()->query("select id from gw_uid where phone ='{$_REQUEST['phone']}' ",'single',true);
		if(!empty($is_new_user)) info('亲,你已经是惠淘会员了!',-1);
		$friend_exisit = M()->query("select sfuid from gw_friend_log where phone = '{$_REQUEST['phone']}' ",'single',true);
		if(!empty($friend_exisit)) info('亲,您已经被邀请过!',-1);
		$add_data = ['phone'=>$_REQUEST['phone'],'sfuid'=>$_REQUEST['user_id']];
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

	/**
	 * [login_log 记录登入]
	 */
	public function login_log()
	{
		if(empty($this->dparam['imei']) && empty($this->dparam['idfa']) && empty($this->dparam['uuid']) && empty($this->dparam['bdid'])) info('设备信息不完整',-1);

		if(empty($this->dparam['user_id'])) info('用户信息不完整',-1);

		$this->dparam['uid'] = $this->dparam['user_id'];
		/**判断是否新用户**/
		$uid_info =  A('Uid:getInfo',["objectId = '{$this->dparam['uid']}'",'*','single']);
		/**判断在did表中是否存在**/
		$did_info = $this->ckDidExisit();

		$this->unionHandle($uid_info,$did_info);

		/**返回用户信息**/
		info(['msg'=>'操作成功','status'=>1]);
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
		$sql = "select * from gw_uid_bill_log where type = 1 and uid = '{$this->dparam['user_id']}' and id in ($bill_ids)";
		$data = M()->query($sql,'all');

		foreach ($data as $v){
			$shop = ShopincomeController::getObj();
			$shop -> getReward($v);
		}
	}


	//{"user_id":"123","task_id":["123","222"]}
	/**
	 * [getReward 拆红包(任务)]
	 */
	public function getRewardTask()
	{
		if(empty($_POST['task_id']) || empty($_POST['user_id']))
			info(-1,'缺少参数');
		$data = M()->query("select * from gw_uid_bill_log where type = 2 and uid = '{$_POST['user_id']}' and task_id = {$_POST['task_id']}",'all');
		foreach ($data as $v)
			(TaskincomeController::getObj())->getReward($v);
	}

}
