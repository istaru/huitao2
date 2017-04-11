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
// {
//     "countdown":10,//倒计时的时间
//    "frequency":3,//显示的次数
//     "data": [//图片的地址
//         {
//             "href": "",
//             "url": "http://wapsh.189.cn/dqpimages/12-23-4.jpg"
//         },
//         {
//             "href": "",
//             "url": "http://wapsh.189.cn/dqpimages/12-23-2.jpg"
//         },
//         {
//             "href": "",
//             "url": "http://wapsh.189.cn/dqpimages/12-23-1.jpg"
//         }
//     ],
//     "rs": "0"
// }
	}
}