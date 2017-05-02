<?php
include (DIR_LIB.'alidayu/TopSdk.php');
class VcodeController extends AppController
{
	//验证码有效时间(秒)
	public static $validationCodeTime = 600;
	//验证码位数
	public static $verificationCodeNumber = 4;

	//{"phone":"13482509858"}
	public function sendCode($phone,$code)
	{
		$c = new TopClient;
		$c->appkey = self::ALIDAYU_KEY;
		$c->secretKey = self::ALIDAYU_SECRET;
		$req = new AlibabaAliqinFcSmsNumSendRequest;
		$req->setSmsType("normal");						//模板类型普通
		$req->setSmsFreeSignName("惠淘");				//应用名
		$req->setSmsParam("{\"code\":\"".$code."\"}");	//发送的数据,配合阿里大于模板渲染
		$req->setRecNum($phone);						//发送的手机
		$req->setSmsTemplateCode("SMS_34615397");		//选用的模板
		$resp = $c->execute($req);
		return $resp->result->success;
	}

	//localhost/shopping/id_code
	//{"phone":"13482509858","type":"1"}
	public function codeHandle() {
		if(empty($this->dparam['phone'])) info('手机号不存在',-1);
		if(empty($this->dparam['type'])) info('操作异常',-1);
		//判断该用户是否已经注册
		if($this->dparam['type'] == 1)
			if(M('uid')->where(['phone' => ['=', $this->dparam['phone']]])->select('single')) info('该手机号已经注册过了', -1);

		if($v_info = M('vaild_log')->where("phone = {$this->dparam['phone']} and type = {$this->dparam['type']}")->order('createdAt DESC')->limit(1)->select('single')) {
			$codeTime = time() - $v_info['expire'];
			//验证码有效时间验证 禁止频繁发送
			if($codeTime < self::$validationCodeTime) {
				if((self::$validationCodeTime - $codeTime) < 100)
					info('您上个验证码还未过期,'.(self::$validationCodeTime - $codeTime).'秒后再试', -1);
				else
					info('您上个验证码还未过期,'.(round((self::$validationCodeTime - $codeTime) / 60)).'分钟后再试', -1);
			}
		}
		$this->generateVerificationCode(self::$verificationCodeNumber, $this->dparam['phone'], $this->dparam['type']) ? info('验证码已发送', 1) : info('操作异常', -1);
	}
	public function generateVerificationCode($length, $phone, $type = '') {
		$code = randstr($length);
		$u_info = A('Uid:getInfo',["phone = {$phone}",'*','single']);
		$v_data = [
			'vaild_code' => $code,
			'phone' 	 => $phone,
			'type'		 => $type,
			'expire' 	 => time(),
			'uid'	 	 => $u_info ? $u_info['objectId'] : '',
		];
		//验证码入库成功
		if (A('Vaild:addVcode',[$v_data]))
			return $this->sendCode($this->dparam['phone'],$code) ? : 0;
		return;
	}
}