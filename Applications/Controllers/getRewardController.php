<?php
abstract class getRewardController
{
	public $sql = [];


	/**
	* [createSql 生成sql]
	*/
	function createSql($data,$status,$price,$msg)
	{
		//增加uid_log
		$this->sql[] = " insert into gw_uid_log (status,uid,order_id,score_type,score_source,score_info,price) values ({$status},'{$data['uid']}',{$data['order_id']},1,'{$data['score_source']}','{$msg}',{$price}) ";

		//更新红包消息状态
		$this->sql[] = "update gw_message set status = 2 where uid = '{$data['uid']}' and status = 1 and type = 2 and bid = {$data['id']}";

		//新消息
		$this->sql[] = "insert into gw_message (uid,bid,content,type) values ('{$data['uid']}',{$data['id']},'{$msg}',1)";

		//增加收入明细
		$this->sql[] = "insert into gw_income_log (status,uid,order_id,score_type,score_source,score_info,price) values ({$status},'{$data['uid']}',{$data['order_id']},1,'{$data['score_source']}','{$msg}',{$price}) ";

		//更新此条账单状态
		$this->sql[] = "update gw_uid_bill_log set status = 2 where id = {$data['id']}";
	}


	/**
	* [execSql 执行sql]
	*/
	function execSql()
	{
		M()->startTrans();
		try {

			foreach ($this->sql as $v)
				M()->query($v);
			echo 'ok';
		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();
	}
}