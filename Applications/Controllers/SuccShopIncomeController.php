<?php
class SuccShopIncomeController
{
	public static $obj;
	public $sql = [];
	private $percent = 0.7;
	private $percentsf = 0.2;
	private function __construct(){}


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
				if(empty($v['cost']) || (empty($v['uid']) &&  empty($v['sfuid'])) || empty($v['rating'])) continue;

				##!********* source=0 给师傅返利 不给本人返利
				if($v['source'] != 0){
					//购买的用户
					$sql = "INSERT IGNORE INTO ngw_uid_bill_log (type,uid,order_id,score_type,score_source,score_info,cost,rating,report_date) VALUES (1,'{$v['uid']}','{$v['order_id']}',8,'','购买奖励',".$v['cost']*$v['rating']/100*$this->percent.",{$v['rating']},'".(date('Y-m-d',time()))."')";
					M()->query($sql);

					//取出账单id
					$bid = M()->getLastInsertId();
					if($bid){	//得到账单 id 并新增一条红包消息用户拆红包
						$sql = "INSERT IGNORE INTO ngw_message (uid,bid,content,type) VALUES ('{$v['uid']}',$bid,'购物红包',2)";
						M()->query($sql);
					}
				}

				##***********************
				if(!empty($v['sfuid'])){
					$sql = "INSERT IGNORE INTO ngw_uid_bill_log (type,uid,order_id,score_type,score_source,score_info,cost,rating,report_date) VALUES (1,'{$v['sfuid']}','{$v['order_id']}',1,'{$v['uid']}','好友购买奖励',".$v['cost']*$v['rating']/100*$this->percentsf.",{$v['rating']},'".(date('Y-m-d',time()))."')";
					M()->query($sql);
					$bid = M()->getLastInsertId();
					if($bid){
						$sql = "INSERT IGNORE INTO ngw_message (uid,bid,content,type) VALUES ('{$v['sfuid']}',$bid,'好友购物红包',2)";
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
		//检查这些订单是否补全并且状态正确
		$sql = "SELECT distinct(a.order_id),b.paid_fee cost ,a.uid,a.source,a.rating,c.sfuid
				FROM ngw_order a JOIN ngw_order_status b ON a.order_id = b.order_id
				JOIN ngw_uid c ON  a.uid = c.objectId
				WHERE a.order_id IN (".implode(',',$order_list).")
				AND a.status = 1 AND b.status = 2 ";
		$o_info = M()->query($sql,'all');
		// D($o_info);die;
		return $o_info;
	}
}