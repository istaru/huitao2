<?php
class UserincomeController extends AppController
{
	public $shop = null;


	//{"user_id":"123","bid":["123","321","222"]}
	/**
	 * [getReward 拆红包(购买)]
	 */
	public function getReward()
	{
		if(empty($this->dparam['bid']) || empty($this->dparam['user_id']))
			info(-1,'数据不完整');
		$bill_ids = implode(',',$this->dparam['bid']);
		//取出用户拆红包对应的所有账单
		$sql = "select * from gw_uid_bill_log where type = 1 and uid = '{$this->dparam['user_id']}' and id in ($bill_ids)";
		$data = M()->query($sql,'all');

		foreach ($data as $v){
			$shop = ShopincomeController::getObj();
			$shop -> getReward($v);
		}
	}


	//{"user_id":"123","task_id":["123"]}
	/**
	 * [getReward 拆红包(购买)]
	 */
	public function getRewardTask()
	{
		if(empty($this->dparam['task_id']) || empty($this->dparam['user_id']))
			info(-1,'数据不完整');
		$task_ids = implode(',',$this->dparam['task_id']);
		$sql = "select * from gw_uid_bill_log where type = 2 and uid = '{$this->dparam['user_id']}' and task_id in ($task_ids)";
		$data = M()->query($sql,'all');

		foreach ($data as $v){
			$shop = TaskincomeController::getObj();
			$shop -> getReward($v);
		}
	}

}