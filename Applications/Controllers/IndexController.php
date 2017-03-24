<?php
class IndexController extends AppController
{
	/**
	 * [banners 首页banner]
	 */
	public function banners()
	{
		$banners = [
				[
					"icon_url" => RES_SITE.'shoppingResource/banners/banner01.png',
					'link' => '',
				],
				[
					'icon_url' => RES_SITE.'shoppingResource/banners/banner02.png',
					'link' => '',
				],
				[
					'icon_url' => RES_SITE.'shoppingResource/banners/banner03.png',
					'link' => '',
				],
				[
					'icon_url' => RES_SITE.'shoppingResource/banners/banner04.png',
					'link' => './my.html#guide',
				],
		];

		info('请求成功',1,$banners);
	}


	/**
	 * [opening app开画页]
	 */
	public function opening()
	{

	}
}