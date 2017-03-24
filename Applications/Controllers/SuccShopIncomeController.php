<?php
class SuccShopIncomeController
{
	public static $obj;
	public $sql = [];

	private function __construct()
	{

	}


	public static function getObj()
	{
		if(!(self::$obj instanceof self))
			self::$obj = new self;
		return self::$obj;
	}


	//$order_list = ['2946881213043222','2946998613943222']
	/**
	 * [buySuccess 订单返利生成预估收入]
	 */
	public function incomeHandle($order_list)
	{
		if(empty($order_list)) return;

		$o_info = $this->orderInfo($order_list);

		M()->startTrans();
		try {

			foreach ($o_info as $k => $v) {
				//数据不全则跳过
				if(empty($v['cost']) || empty($v['uid']) || empty($v['rating'])) continue;

				//购买的用户
				$sql = "INSERT IGNORE INTO gw_uid_bill_log (type,uid,order_id,score_type,score_source,score_info,cost,rating,report_date) VALUES (1,'{$v['uid']}','{$v['order_id']}',8,'','购买奖励',{$v['cost']},{$v['rating']},'".(date('Y-m-d',time()))."')";
				M()->query($sql);

				//取出账单id
				$bid = M()->getLastInsertId();
				if($bid){
					$sql = "INSERT IGNORE INTO gw_message (uid,bid,content,type) VALUES ('{$v['uid']}',$bid,'购物红包',2)";
					M()->query($sql);
				}

				if(!empty($v['sfuid'])){
					$sql = "INSERT IGNORE INTO gw_uid_bill_log (type,uid,order_id,score_type,score_source,score_info,cost,rating,report_date) VALUES (1,'{$v['sfuid']}','{$v['order_id']}',1,'{$v['uid']}','好友购买奖励',{$v['cost']},{$v['rating']},'".(date('Y-m-d',time()))."')";
					M()->query($sql);
					$bid = M()->getLastInsertId();
					if($bid){
						$sql = "INSERT IGNORE INTO gw_message (uid,bid,content,type) VALUES ('{$v['sfuid']}',$bid,'好友购物红包',2)";
						M()->query($sql);
					}
				}
				echo "order_id :  {$v['order_id']} is ok..      ";
			}


		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();
	}



	/**
	 * [orderInfo 查询订单及需要的数据]
	 */
	public function orderInfo($order_list)
	{
		//取出订单以及关联数据
		$sql = "select distinct(o.order_id) , o.uid , if(o.cost<o.deal_price,o.cost,o.deal_price) as cost , o.rating,o.amount,u.sfuid from gw_order o join gw_uid u on o.uid = u.objectId where o.order_id in (".implode(',',$order_list).") and o.status = 1 ";
		$o_info = M()->query($sql,'all');
		// D($o_info);die;
		return $o_info;
	}
}