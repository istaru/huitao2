<?php
class ShopincomeController extends getRewardController
{
	private static $shop;
	private $fst_sf = 5;   //徒弟购买师傅首两单的奖励
	private $percent = 0.7;
	private $percentsf = 0.2;

	private function __construct(){

	}


	public static function getObj()
	{
		if(!(self::$shop instanceof self))
			self::$shop = new self;
		return self::$shop;
	}


	public function getReward($data)
	{
		$this->sql = [];
		if(empty($data)) return;
		//检查此账单对应的订单是否退单
		if(!$this->checkBillType($data)){
			$this->execSql();
			return;
		};
		if($data['score_type'] == 8)		//用户下单 自己提成
			$this->rewardRule($data);
		else if($data['score_type'] == 1)	//好友下单 师傅提成
			$this->rewardSwitch($data);
		else if($data['score_type'] == 99)	//老用户迁移
			$this->rewardRuleSuper($data);
		// D($this->sql);die;
		$this->execSql();
	}


	/**
	* [rewardSwitch 返利规则选择]
	*/
	public function rewardSwitch($data)
	{
		$num = $this->checkIncomesNum($data['uid'],$data['score_source']);
		// echo $num;die;
		if($num < 3)   //首两单奖励5元
			$this->sfRewardRuleForFirst($data);
		else
			$this->sfRewardRule($data);

	}


	/**
	* [checkOrdNum 检查提成笔数]
	*/
	public function checkIncomesNum($uid,$score_source)
	{
		$sql = "select id from ngw_uid_log where uid = '{$uid}' and score_source = '{$score_source}'";
		// echo $sql;die;
		$ids = M()->query($sql,'all');
		return count($ids);
	}


	/**
	* [rewardRuleForFirst 徒弟购买首两单对师傅的特殊奖励(5元)]
	*/
	public function sfRewardRuleForFirst($data)
	{
		// D($data);die;
		// if(!$this->checkOrdStatus($data)) return false;

		$this->createSql($data,1,$this->fst_sf,"恭喜您,获得好友下单红包{$this->fst_sf}元!");
	}


	/**
	* [rewardRule 徒弟购买对师傅的普通奖励]
	*/
	public function sfRewardRule($data)
	{
		// $price = $data['cost']*$data['rating']/100*$this->percentsf;  //根据订单的类型计算价格
		$this->createSql($data,1,$data['cost'],"恭喜您,获得好友下单红包{$data['cost']}元!");
	}


	/**
	* [rewardRule 徒弟购买普通奖励]
	*/
	public function rewardRule($data)
	{
		// $price = $data['cost']*$data['rating']/100*$this->percent;  //根据订单的类型计算价格
		$this->createSql($data,1,$data['cost'],"恭喜您,获得下单红包{$data['cost']}元!");
	}

	/**
	* [rewardRule  拆红包特殊情况]
	*/
	public function rewardRuleSuper($data)
	{
		$this->createSql($data,1,$data['cost'],"惠淘全方位大升级,点击领取之前的收入金额!");
	}


	/**
	* [checkOrdStatus 检查账单状态]
	*/
	public function checkBillType($data)
	{

		if($data['status'] == 3){   //退单导致此账单失效
			$this->sql[] = "insert into ngw_message (uid,bid,content,report_date) values ('{$data['uid']}',{$data['id']},'订单{$data['order_id']}退单,导致此红包失效!','".(date('Y-m-d',time()))."')";

			$this->sql[] = "update ngw_message set status = 2 where uid = '{$data['uid']}' and status = 1 and type = 2 and bid = {$data['id']}";
			return false;
		}
		return true;
	}


}