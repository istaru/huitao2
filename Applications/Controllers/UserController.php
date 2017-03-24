<?php
class UserController extends AppController
{


	/**
	 * [person_info 个人中心数据]
	 * {"user_id":"kQb9frCvDR"}
	 */
	//SELECT * FROM gw_uid_log WHERE createdAt < date_sub(curdate(),interval 1 day)
	public function personInfo()
	{
		if(empty($this->dparam['user_id'])) info('user_id不存在',-1);
		//取出用户最近7天的预结收入总和
		$predict_total = M()->query("SELECT sum(price) as predict FROM gw_uid_log WHERE createdAt > date_sub(curdate(),interval 7 day) AND status = 1 AND uid = '{$this->dparam['user_id']}'");
		//取出用户信息
		$uid_info =  A('Uid:getInfo',["objectId = '{$this->dparam['user_id']}'",'*','single']);
		if(empty($uid_info)) info('用户不存在',-1);
		$data = [
			'msg' => '请求成功',
			'status' => 1,
			'sfuid' => $uid_info['sfuid'],
			'nickname' => $uid_info['nickname'],
			'head_img' => $uid_info['head_img'],
			'balance' => (string)$uid_info['price'],	//可用余额
			'predict' => empty($predict_total['predict']) ? '0': (string)$predict_total['predict'],	//预估收入
			'total' => (string)($uid_info['price']+$uid_info['pnow']+$uid_info['pend']),	//总收入
			'withdrawn' => (string)$uid_info['pend'],	//已提现
			'processing' => (string)$uid_info['pnow'],	//提现处理中
		];
		info($data);
	}

	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [withdrawals 提现明细]
	 */
	public function withdrawals()
	{
		$pnow_list = A('Pnow:getPnowInfo',["uid = '{$this->dparam['user_id']}'"]);
		// D($pnow_list);
		if(!empty($pnow_list))
		{
			foreach ($pnow_list as $k => $v) {
				$temp = [];
				if($v['status'] < 4){
					$temp['msg'] = $v['errmsg'];
				}elseif($v['status'] == 5){
					$temp['msg'] = $v['duiba_success'];
				}elseif($v['status'] == 6){
					$temp['msg'] = $v['duiba_end_errmsg'];
				}
				if(empty($temp['msg'])) $temp['msg'] = '请耐心等待';
				$temp['date_time'] = $v['updatedAt'];
				$temp['price'] = $v['price'];
				$temp['status'] = $v['status'];
				$arr[] = $temp;
			}
		}else{
			$arr = [];
		}

		// D($arr);die;
		info('请求成功',1,$arr);
	}
	//验证改用户是否可以绑定好友
	public function checkbindMasters($uid, $sfuid) {
		if($uid == $sfuid)
			return '不允许绑定自己';
		$taobaoInfo = M()->query("SELECT * FROM gw_taobao_log WHERE taobao_id =( SELECT taobao_id FROM gw_uid WHERE objectId = '{$uid}')", 'all');
		if(count($taobaoInfo) > 1)
			return '您已经不是新用户啦';
		else if(count($taobaoInfo) < 1)
			return '请您先淘宝授权';
		//徒弟 师傅如果是一个淘宝授权账号  禁止绑定关系
		$sf = M('uid')->where(['objectId' => ['=', $sfuid]])->select('single');
		if(empty($sf['taobao_id']))
			return '您的好友可能还未允许淘宝授权';
		if($sf['taobao_id'] == $taobaoInfo[0]['taobao_id'])
			return '不允许淘宝授权账号一样的进行绑定好友';
		//用户设备号只有一次记录 才允许绑定好友关系
		$data = M('uid')->where(['objectId' => ['=',$uid]])->field('imei,idfa,bdid,idfa,uuid')->select('single');
		$sql  = "SELECT count(0) sum FROM gw_did_log WHERE( bdid = '{$data['bdid']}') OR( idfa = '{$data['idfa']}' AND uuid = '{$data['uuid']}') OR( imei = '{$data['imei']}')";
		$didNum = M()->query($sql,'single');
		if($didNum['sum'] > 1)
			return '您已经不是新用户啦';
		//杜绝出现互绑情况 比如 1的徒弟是2  1的师傅是2
		$where = [
			'uid' 		 => ['=', $uid],
			'score_type' => ['=', 2],
			['and']
		];
		$n = M('uid_log')->field('score_source')->where($where)->select();
		if(!empty($n)) {
			$n = array_column($n,'score_source');
			if(in_array($sfuid, $n))
				return '他已经是您的好友了呀';
		}
		return true;
	}
	/**
	 * [bindMasters 绑定好友关系]
	 */
	public function bindMasters($type = false, $objectId = '', $sfuid = '')
	{

		//分配变量
		if(!$objectId || !$sfuid) {
			if(!empty($this->dparam['user_id']) && !empty($this->dparam['sfuid'])) {
				$objectId = $this ->dparam['user_id'];
				$sfuid    = $this ->dparam['sfuid'];
			} else {
				$type ? : info('缺少参数');
			}
		}
		/**
		 * 如果是特邀用户则需要先查出来特邀用户的uid
		 */
		$ShiFu = M('uid') ->where("objectId = '{$sfuid}' OR Invitation_code = '{$sfuid}'") ->field('objectId,nickname') ->select('single');
		if(isset($ShiFu['objectId']) && isset($ShiFu['nickname'])) {
			$sfuid = $ShiFu['objectId'];
			$nickname = $ShiFu['nickname'];
		}
		else {
			return $type ? : info('该好友账号可能还未注册');
		}
		/**
		 * 判断该用户是否存在表中
		 */
		$user = M('uid')->field('sfuid,nickname')->where(['objectId'=> ['=',$objectId]])->select('single');
		if(!$user)
			return $type ? : info('您赶紧去登录吧');
		/**
		 * 判断该用户之前是否绑定过师傅
		 */
		if(!empty($user['sfuid']))
			return $type ? : info('您已经填写过邀请人了');
		$nick = $user['nickname'];
		$value = $this->checkbindMasters($objectId, $sfuid);
		if($value !== true && $type === false)
			info($value,-1);
		else if($value !== true && $type === true)
			return;
		/**
		 * 绑定师徒关系  失败直接抛出异常进行回滚
		 */
		try {
			M()->startTrans();
			$arr = [
				'uid' 			=> $sfuid,
				'score_source'	=> $objectId,
				'score_info'	=> '绑定好友',
				'score_type'	=> 2,
				'status'		=> 2,
			];
			/**
			 * [$a 进行消息通知用户]
			 */
			$a = [
				[
					'uid'		 => $sfuid,
					'content'	 => $nick.'绑定了您为好友'
				],
				[
					'uid'		=> $objectId,
					'content'	=> '您成功绑定了'.$nickname.'为好友'
				],
			];
			//如果是首次邀请徒弟则奖励2元 其余不奖励
			if(!M('uid_log')->where(['uid' => ['=', $sfuid], 'score_type' => ['=', 2]],['and'])->field('id')->count()) {
				$sql = "UPDATE gw_uid SET price = price+2,invitation_count = invitation_count+1 WHERE objectId='{$sfuid}'";
				M()->query($sql);
				$arr['price']  		= 2;
				$arr['score_info']	= '首次绑定好友奖励2元';
			} else {
				$sql = "UPDATE gw_uid SET invitation_count = invitation_count+1 WHERE objectId='{$sfuid}'";
				M()->query($sql);
			}
			$arr['status'] 		= 2;
			M('uid')->where(['objectId' => ['=',$objectId]])->save(['sfuid' => $sfuid]) or E('绑定失败');

			M('uid_log')->add($arr) or E('绑定失败');

			M('message')->batchAdd($a) or E('绑定失败');

		} catch(Exception $e) {
			M()->rollback();
			if(!$type)
				info($e->getMessage(),-1);
		}
		M()->commit();
		if(!$type)
			info('绑定成功');
		else
			return true;
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
		if(empty($this->dparam['imei']) && empty($this->dparam['idfa']) && empty($this->dparam['uuid']) && empty($this->dparam['bdid'])) info('用户信息不完整',-1);

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


	//{"user_id":"123","task_id":["123"]}
	/**
	 * [getReward 拆红包(任务)]
	 */
	public function getRewardTask()
	{
		if(empty($this->dparam['task_id']) || empty($this->dparam['user_id']))
			info(-1,'数据不完整');
		$task_ids = implode(',',$this->dparam['task_id']);
		$sql = "select * from gw_uid_bill_log where type = 2 and uid = '{$this->dparam['user_id']}' and task_id in ($task_ids)";
		$data = M()->query($sql,'all');

		foreach ($data as $v){
			$shop = TaskincomeController::getObj();
			$shop -> getReward($v);
		}
	}

}
