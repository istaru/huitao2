<?php
class TaskincomeController extends getRewardController
{
	private static $task;


	private function __construct(){
	}


	public static function getObj(){
		if(!(self::$task instanceof self))
			self::$task = new self;
		return self::$task;
	}


	/**
	* [getRewardForTask 拆红包(任务)]
	*/
	public function getRewardForTask($data)
	{
		//直接余额
		$this->sql[] = " update gw_uid set price = price + {$data['cost']} where objectId = {$data['uid']} ";
		$this->createSql($data,2,$data['cost'],"恭喜您,获得任务红包{$data['cost']}元!");
	}
}