<?php
class FailShopIncomeController
{
	public static $obj;
	public $sql = [];
	public $msg = 'INSERT INTO ngw_message (uid,content,lid,report_date) VALUES ';
	public $income = 'INSERT INTO ngw_income_log (order_id,uid,status,score_source,score_type,score_info,price) VALUES ';

	private function __construct()
	{

	}


	public static function getObj()
	{
		if(!(self::$obj instanceof self))
			self::$obj = new self;
		return self::$obj;
	}

	//$order_list=['2623258984503529','3131590033367715']
	/**
	 * [buyHandle 购买不成立后的退款]
	 */
	public function incomeHandle($order_list)
	{
		if(empty($order_list)) return;

		//更新退单后账单表的状态
		$order_list = implode(',',$this->checkBill($order_list));

		//查询订单返利去向
		$estimate_res	=	$this->orderEstimate($order_list);	//预估
		$cash_res		=	$this->orderCash($order_list);		//余额

		if(empty($estimate_res) && empty($cash_res)) return;

		!empty($estimate_res)	&&	$this->estimateHandle($estimate_res);
		!empty($cash_res) 		&&	$this->cashHandle($cash_res);

		$this->sql[] = rtrim($this->msg,',');;
		$this->sql[] = rtrim($this->income,',');

		$this->execSql();
	}

	/**
	* [execSql 执行sql]
	*/
	public function execSql()
	{
		M()->startTrans();
		try {

			foreach ($this->sql as $v)
				M()->query($v);

		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();
		echo 'ok';
	}

	/**
	 * [cashHandle 余额处理]
	 */
	public function cashHandle($data)
	{
		D($data);
		$order_list = '';
		$uids = [];	//要处理的用户id列表
		foreach ($data as $k => $v) {

			//将同一个uid 所有已经转余额但是要退的订单的,佣金相加
			if(!array_key_exists($v['uid'],$uids))
				$uids[$v['uid']] = ['uid'=>$v['uid'],'price'=>$v['price']];
			else
				$uids[$v['uid']]['price'] += $v['price'];


			//余额订单列表
			$order_list .= $v['order_id'];

			//消息
			$this->msg .= "('{$v['uid']}','{$v['score_source']}对订单{$v['order_id']}进行了退款操作,将扣除之前的{$v['price']}元奖励哦!',{$v['id']},'".(date('Y-m-d',time()))."'),";

			//收入明细
			$this->income .= "('{$v['order_id']}','{$v['uid']}',4,'{$v['score_source']}','{$v['score_type']}','{$v['score_source']}对订单{$v['order_id']}进行了退款操作,将扣除之前的{$v['price']}元奖励哦!',{$v['price']}),";
		}

		//批量更新uid中余额的sql
		$str = '';
		foreach($uids as $kk => $vv)
			$str .= " WHEN '{$vv['uid']}' THEN {$vv['price']} ";	//拼接所有用户扣余额的sql
		$this->sql[] = "UPDATE ngw_uid SET price = price - CASE objectId".$str."ELSE 0 END";

		//修改用户日志的sql
		$this->sql[] = "UPDATE ngw_uid_log SET status = 4 , score_info = '退单扣除之前的奖励' WHERE status = 2 AND order_id IN ({$order_list})";
	}


	/**
	 * [estimateHandle 预估的处理]
	 */
	public function estimateHandle($data)
	{
		$order_list = '';
		foreach ($data as $k => $v) {
			//余额订单列表
			$order_list .= $v['order_id'];

			//消息
			$this->msg .= "('{$v['uid']}','{$v['score_source']}对订单{$v['order_id']}进行了退款操作,将扣除之前的{$v['price']}元奖励哦!',{$v['id']},'".(date('Y-m-d',time()))."'),";

			//收入明细
			$this->income .= "('{$v['order_id']}','{$v['uid']}',3,'{$v['score_source']}','{$v['score_type']}','{$v['score_source']}对订单{$v['order_id']}进行了退款操作,将扣除之前的奖励哦',{$v['price']}),";
		}

		//修改用户日志的sql
		$this->sql[] = 	 "UPDATE ngw_uid_log SET status = 3 , score_info = '退单扣除之前的奖励' WHERE status = 1 AND order_id IN ({$order_list})";
	}


	/**
	 * [orderEstimate 查询在预估中的记录]
	 */
	public function orderEstimate($order_list)
	{
		if(empty($order_list)) return;
		$sql = "select id,uid,price,status,order_id,score_source,score_type,score_info from ngw_uid_log where status = 1 and order_id in ({$order_list})";
		$info = M()->query($sql,'all');
		// D($info);die;
		return $info;
	}

	/**
	 * [orderCash 查询已转到余额的记录]
	 */
	public function orderCash($order_list)
	{
		if(empty($order_list)) return;
		$sql = "select id,uid,price,status,order_id,score_source,score_type,score_info from ngw_uid_log where status = 2 and order_id in ({$order_list})";
		$info = M()->query($sql,'all');
		// D($info);die;
		return $info;
	}

	/**
	 * [checkBill 还未拆的红包则直接帮账单状态改成作废]
	 */
	public function checkBill($order_list)
	{
		if(empty($order_list)) return;
		$str = implode(',',$order_list);
		//修改退单账单状态
		$this->sql[] = "update ngw_uid_bill_log set status = 3 where order_id in ({$str})";
		return $order_list;
	}
}