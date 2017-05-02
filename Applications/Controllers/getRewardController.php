<?php
abstract class getRewardController
{
	public $sql = [];

	/**
	* [createSql 生成sql]
	*/
	function createSql($data,$status,$price,$msg)
	{
		//订单id 以及 任务id  默认值设为NULL
		$order_id = !empty($data['order_id']) ? $data['order_id'] : 'NULL';
		$task_id  = !empty($data['task_id'])  ? $data['task_id']  : 'NULL';
		//更新此条账单状态
		$this->sql[] = "update ngw_uid_bill_log set status = 2 where id = {$data['id']}";
		//增加uid_log
		$this->sql[] = "insert IGNORE into ngw_uid_log (status,uid,order_id,task_id,score_type,score_source,score_info,price) values ({$status},'{$data['uid']}',{$order_id},{$task_id},1,'{$data['score_source']}','{$msg}',{$price}) ";

		if($order_id !== 'NULL') {
			//更新红包消息状态
			$this->sql[] = "update ngw_message set status = 2 where uid = '{$data['uid']}' and status = 1 and type = 2 and bid = {$data['id']}";
			//新消息
			$this->sql[] = "insert IGNORE into ngw_message (uid,bid,content,type) values ('{$data['uid']}',{$data['id']},'{$msg}',1)";
		}
		//增加收入明细
		$this->sql[] = "insert IGNORE into ngw_income_log (status,uid,order_id,task_id,score_type,score_source,score_info,price) values ({$status},'{$data['uid']}',{$order_id},{$task_id}, 1,'{$data['score_source']}','{$msg}',{$price}) ";


		// D($this->sql);

	}


	/**
	* [execSql 执行sql]
	*/
	function execSql()
	{
		// D($this->sql);die;
		M()->startTrans();
		try {
			foreach ($this->sql as $v)
				M()->query($v);
		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();
	}
}