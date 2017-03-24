<?php
class BannersController
{
	public function banners()
	{
		$data = [
			'status' => 1,
			'msg' => '请求成功',
			'data' => [
				[
					// 'icon_url' => URL_SITE."resource/img/banner/1.jpg",
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
			]
		];
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
    	exit;

	}
}