<?php
include (DIR_LIB.'alidayu/TopSdk.php');
date_default_timezone_set('Asia/Shanghai');

class VcodeController extends AppController
{
	//{"phone":"13482509858"}
	public function sendCode($phone,$code)
	{
		$c = new TopClient;
		$c->appkey = self::ALIDAYU_KEY;
		$c->secretKey = self::ALIDAYU_SECRET;
		$req = new AlibabaAliqinFcSmsNumSendRequest;
		$req->setSmsType("normal");
		$req->setSmsFreeSignName("惠淘");
		$req->setSmsParam("{\"code\":\"".$code."\"}");
		$req->setRecNum($phone);
		$req->setSmsTemplateCode("SMS_34615397");
		$resp = $c->execute($req);
		return $resp->result->success;
	}

	//localhost/shopping/id_code
	//{"phone":"13482509858","type":"1"}
	public function codeHandle()
	{
		if(empty($this->dparam['phone'])) info('手机号不存在',-1);
		if(empty($this->dparam['type'])) info('类型不正确',-1);
		$v_info = A('Vaild:getVcodeByPhone',[$this->dparam['phone']]);
		$now = time();
		if(($now - $v_info['expire']) > 600 )	//防止频繁发送 大于300秒
		{
			$code = randstr(4);

			$u_info = A('Uid:getInfo',["phone = {$this->dparam['phone']}",'*','single']);
			$v_data = [
				'vaild_code' => $code,
				'phone' => $this->dparam['phone'],
				'type'	=> $this->dparam['type'],
				'expire' => time(),
			];
			!empty($v_data) ? $v_data['uid'] = $u_info['objectId'] : '';
			$v_add = A('Vaild:addVcode',[$v_data]);

			if ($v_add) {	//记录成功
				$sendCode = $this->sendCode($this->dparam['phone'],$code);
				if($sendCode)
				{	//发送成功
					info('验证码已发送',1);
				}else{	//发送失败
					info('请求频繁',-1);
				}
			}else{	//记录失败
				info('验证码数据异常',-1);
			}
		}else{
			info('您的操作过于频繁,5分钟后再试',-1);
		}
	}

}