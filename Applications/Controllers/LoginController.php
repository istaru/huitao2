<?php
class LoginController extends AppController
{
	public $uid_info = NULL;

	//{"uuid":"Z203e0d03df9c410784e08d966ea9be09","idfa":"ZE1D62B8-DE2D-4266-9EC2-8ED4F55E67AB","deviceVer":"10.0.2","phone":"13482509859","id_code":"1648","type":"0"}

	/**
	 * [login 登入]
	 */
	public function login()
	{
		/**开启事务**/
		M()->startTrans();
		try {
			$this->checkParam();
			$this->checkUid();

			//存在验证码码表示修改密码登入
			if(!empty($this->dparam['id_code']))
				$this->checkCode();
			else
				$this->checkPwd();

			$this->checkDid();
			$this->unionHandleForLogin();
			$this->unionHandle();
			// D($this->dparam);die;
		} catch (Exception $e) {
			M()->rollback();
			info('网络异常!',-1);
		}
		M()->commit();
		$this->optInfo(['msg'=>'登入成功!','status'=>1]);
	}


	/**
	 * [register 注册]
	 */
	public function register()
	{
		/**开启事务**/
		M()->startTrans();
		try {
			$this->checkParam();
			$this->checkCode();
			$this->checkDid();
			$this->uidRegister();
			$this->unionHandle();
		} catch (Exception $e) {
			M()->rollback();
			info('网络异常!',-1);
		}
		M()->commit();
		$this->optInfo(['msg'=>'注册成功!','status'=>1]);

	}


	/**
	 * [optInfo 返回前端]
	 */
	public function optInfo($data)
	{
		$sql = " SELECT objectId AS user_id,nickname,Invitation_code AS invite FROM gw_uid WHERE phone = '{$this->dparam['phone']}'";
		$info = M()->query($sql,'single');
		info($data+$info);
	}


	/**
	 * [checkUid 检查用户]
	 */
	public function checkUid()
	{
		//检查是否老用户
		$sql = " SELECT * FROM gw_uid WHERE phone = {$this->dparam['phone']} ";
		$this->uid_info = M()->query($sql,'single');
		if(empty($this->uid_info)) info('请先注册!',-1);

		$this->dparam['uid']			= $this->uid_info['objectId'];
		$this->dparam['report_date']	=	date('Y-m-d',time());

		return true;
	}


	/**
	 * [unionHandleForLogin 附属表数据增加更新]
	 */
	public function unionHandleForLogin()
	{
		if(!strstr($this->dparam['did'],$this->uid_info['did_list'])){
			$data = $this->dparam;
			$data['did']		= $this->uid_info['id'];
			$data['did_list']	= $this->uid_info['did_list'].','.$this->dparam['did'];
			$data['did_count']	= $this->uid_info['did_count'] + 1;
			$data['logintime']	=	time();

			//修改密码 重置token
			if(!empty($this->dparam['id_code'])){
				$data['token']		= md5($this->dparam['phone'].time());
				$data['password']	= $this->dparam['password'];
			}
			M('uid')->where(" objectId = '{$this->uid_info['objectId']}' ")->save($data);
		}
	}


	/**
	 * [unionHandle 附属表数据增加]
	 */
	public function unionHandle()
	{
		//新增设备日志
		M('did_log')->add($this->dparam,'ignore');
		//新增登入日志
		M('uid_login_log')->add($this->dparam);
	}


	/**
	 * [uidRegister 注册用户]
	 */
	public function uidRegister()
	{
		$num = M()->query('select id from gw_uid order by id desc limit 1');
		$this->dparam['did_list']		=	$this->dparam['did'];
		$this->dparam['logintime']		=	time();
		$this->dparam['objectId']		=	$this->createRandomOnlyStr();
		$this->dparam['nickname']		=	$this->dparam['objectId'];
		$this->dparam['uid']			=	$this->dparam['objectId'];
		$this->dparam['Invitation_code']=	generateInvitationCode($num['id']);
		$this->dparam['head_img']		=	RES_SITE."shoppingResource/head/".rand(1,2).".jpg";
		$this->dparam['password']		=	md5($this->dparam['password']);
		$this->dparam['token']			=	md5($this->dparam['phone'].time());

		M('uid')->add($this->dparam);
	}


	/**
	 * [didRegister 注册设备信息]
	 */
	public function didRegister()
	{
		$this->dparam['report_date']	=	date('Y-m-d',time());
		$this->dparam['did']			=	M('did')->add($this->dparam);
	}


	/**
	 * [checkPwd 检查密码]
	 */
	public function checkPwd()
	{
		($this->uid_info['password'] != md5($this->dparam['password'])) && info('密码不正确!',-1);
		return true;
	}


	/**
	 * [checkVcode 检查验证码]
	 */
	public function checkCode()
	{
		empty($this->dparam['id_code']) && info('请输入验证码!',-1);
		//查询6分钟内的该手机号对应验证码
		$sql	=	" SELECT * FROM gw_vaild_log WHERE type = 1 AND phone = '{$this->dparam['phone']}' AND expire > ".(time()-600);
		$info	=	M()->query($sql,'single');
		if(empty($info['vaild_code']) || $info['vaild_code'] != $this->dparam['id_code']) info('验证码不正确!',-1);

		return true;
	}


	/**
	 * [checkDid 检查设备]
	 */
	public function checkDid()
	{
		$uuid	= !empty($this->dparam['uuid']) ? $this->dparam['uuid'] : '';
		$idfa	= !empty($this->dparam['idfa']) ? $this->dparam['idfa'] : '';
		$bdid	= !empty($this->dparam['bdid']) ? $this->dparam['bdid'] : '';
		$imei	= !empty($this->dparam['imei']) ? $this->dparam['imei'] : '';

		$sql	= " SELECT * FROM gw_did WHERE (uuid = '{$uuid}' AND idfa = '{$idfa}')
											OR (bdid = '{$bdid}')
											OR (imei = '{$imei}')";

		//检查设备是否存在
		$did_info = M()->query($sql,'single');
		if(empty($did_info))
			$this->didRegister();
		else
			$this->dparam['did'] = $did_info['id'];

		return true;
	}


	/**
	 * [checkParam 参数合法验证]
	 */
	public function checkParam()
	{
		//检查设备信息完整性
		!isset($this->dparam['type']) && info('参数不完整',-1);

		if($this->dparam['type'] == 1)
			empty($this->dparam['imei']) && info('设备信息不完整',-1);
		else
			if((empty($this->dparam['uuid']) || empty($this->dparam['idfa'])) && (empty($this->dparam['bdid'])))
				info('设备信息不完整',-1);

		//检查手机号码
		!preg_match("/^1[34578]\d{9}$/",$this->dparam['phone']) && info('非法手机号',-1);
	}


	/**
	 * [createRandomOnlyStr 生成不重复随机字串]
	 */
	protected function createRandomOnlyStr(){
		$i = 0;
		do {
			$add = null;
			if($i>3)
				$output = randstr(11,'MIX');
			else
				$output = randstr(10,'MIX');

			$ck = M()->query("SELECT count(0) as count FROM gw_uid WHERE `objectId` = '".$output."'",'single');
			$i++;
		} while ((int)$ck['count'] > 0);
		return $output;
	}

}