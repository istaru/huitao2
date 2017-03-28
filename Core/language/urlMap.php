<?php
/**
 * url映射方法控制器
 */
return [
	'login'                => ['user','login'],						//登入
	'personal_info'        => ['user','personInfo'],				//个人中心数据
	'withdrawals'          => ['user','withdrawals'],				//提现明细
	// 'password'             => ['user','setPassword'],				//设置密码
	'messages'			   => ['user','message'],					//消息
	'incomes'			   => ['incomes','incomesInfo'],			//收入明细
	'id_code'              => ['vcode','codeHandle'],				//发送验证码
	'withdraw'             => ['duiba','exchange'],					//申请提现
	'banners'              => ['banners','banners'],				//banner页
	'goods'                => ['goodsshow','goods'],                //商品列表
	'detail'			   => ['goodsshow','goodsDetail'],			//商品详情
	'invitations'          => ['invitations','invitations'],        //邀请好友列表
	'invitate_info'        => ['invitations', 'invitateInfo'],      //好友邀请排行榜
	'types'         	   => ['goodsshow','getTypes'],				//商品类型
	'authInfo'			   => ['TaoBaoAuth', 'authInfo'],			//淘宝授权
	'bindMasters'		   => ['User', 'bindMasters'],				//绑定好友关系
	'run_course'		   => ['Newbietask','runCourse'],			//看完新手教程得钱
	'share_goods'		   => ['Newbietask','shareGoods'],			//看完新手教程得钱
	'day_log'			   => ['user','login_log'],					//记录每次打开app
	'goods_switch'		   => ['goodsshow','goodsSwitch'],			//商品上下架操作
];