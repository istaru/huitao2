<?php
class IndexController extends AppController
{
	/**
	 * [banners 首页banner]
	 */
	public function banners()
	{
		$files = scandir(DIR_RES.'img/banner');
		$banners = [];

		foreach ($files as $v) {
			if(strlen($v) > 5 && (explode('.',$v))[1] == 'png'){
				$banners[] =	['icon_url'	=> RES_SITE.'resource/img/banner/'.$v,
									'link'	=> '',
								];
			}
		}

		info('请求成功',1,$banners);
	}

	//{"app_ver":"1.0.5"}
	/**
	 * [opening app开画页]
	 */
	public function opening()
	{
		$app_ver = '';	//预定义的版本.IOS 匹配版本
		$files = scandir(DIR_RES.'img/opening');
		$banners = [];
		foreach ($files as $v) {
			if(((explode('.',$v))[1] == 'png') || ((explode('.',$v))[1] == 'gif')){
				$banners[] =	['icon_url'	=> RES_SITE.'resource/img/opening/'.$v,
									'link'	=> '',
								];
			}
		}
		$data = [
				'status'=>1,
				'msg'=>'请求成功!',
				'data'=>$banners,
				'countdown'=>10,	//倒计时的时间
				'frequency'=>3,		//显示的次数
				'openver'=>2,
			];
		if(!empty($this->dparam['app_ver']) && $this->dparam['app_ver'] == '1.0.5')
			$data['isuser'] = 0;
		else $data['isuser'] = 1;
		if(!empty($this->dparam['user_id'])){
			$sql = "SELECT phone FROM ngw_uid WHERE `objectId` = '{$this->dparam['user_id']}'";
			$phone = M()->query($sql);
			$data['phone'] = $phone['phone'];
		}
		info($data);

	}
}