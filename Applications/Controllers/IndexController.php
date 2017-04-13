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


	/**
	 * [opening app开画页]
	 */
	public function opening()
	{
		$files = scandir(DIR_RES.'img/opening');
		$banners = [];
		foreach ($files as $v) {
			if(((explode('.',$v))[1] == 'png') || ((explode('.',$v))[1] == 'gif')){
				$banners[] =	['icon_url'	=> RES_SITE.'resource/img/opening/'.$v,
									'link'	=> '',
								];
			}
		}
		info([
				'status'=>1,
				'msg'=>'请求成功!',
				'data'=>$banners,
				'countdown'=>10,	//倒计时的时间
				'frequency'=>3		//显示的次数
			]);

	}
}