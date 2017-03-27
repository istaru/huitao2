<?php
/**
 * 收入
 */
class IncomesController extends AppController
{
	//{"user_id":"NPfk0woYpJ"}
	/**
	 * [incomesList 收入明细]
	 */
	public function incomesInfo()
	{
		$uidLog_list = M()->query("SELECT l.createdAt as date_time,u.nickname as friend_name,l.price,l.status,l.score_info FROM gw_income_log l JOIN gw_uid u ON l.score_source = u.objectId WHERE uid = '{$this->dparam['user_id']}'",'all');
		foreach ($uidLog_list as $k => &$v) {
			if($v['status'] == 3) $v['price'] = $v['price'] * -1;
			$v['msg'] = $v['score_info'];
			$v['date_time'] = substr($v['date_time'], 0, -3);
			unset($v['status']);
		}
		// D($uidLog_list);die;
		info('请求成功',1,$uidLog_list);
	}


	//$order_list = ['2946881213043222','2946998613943222']
	/**
	 * [predictBalance 购买成功生成预估收入]
	 */
	public function buySuccess($order_list=[])
	{
		// D($order_list);
		// $order_list = $this->dparam['order_list'];
		if(empty($order_list)) return;
		##查询订单信息提取提成
		$list = implode(',',$order_list);
		$order_info = M()->query("select distinct(o.order_id) , o.uid , o.cost ,o.deal_price , o.rating,o.amount,u.sfuid from gw_order o join gw_uid u on o.uid = u.objectId where o.order_id in ({$list}) and o.status = 1 ",'all');
		$str = '';
		$str2 = '';
		$str3 = '';
		// D($order_info);die;
		$income_num = [];
		$order_uids = [];	//购买人列表
		foreach ($order_info as $kk => $vv) {
			$order_uids[] = $vv['uid'];	//购买人列表
			$uid_income_num = $vv['sfuid'].'-'.$vv['uid'];
			if(array_key_exists($uid_income_num,$income_num)){
				$income_num[$uid_income_num] += 1;
			}else{
				$income_num[$uid_income_num] = 1;
			}
		}
		// D($income_num);die;
		##循环处理每个订单
		foreach ($order_info as $k => $v)
		{
			if(empty($v['cost']) || empty($v['deal_price']) || empty($v['uid']) || empty($v['rating']) || empty($v['sfuid'])) continue;
			// ##检查是否有师傅
			// $uid_info =  A('Uid:getInfo',["objectId = '{$v['uid']}'",'*','single']);
			// if(empty($uid_info) || empty($uid_info['sfuid'])) continue; //没有邀请人 或者对应用户不存在跳过


			$num = M()->query("select count(*) as num from gw_uid_log where uid = '{$v['sfuid']}' and score_source = '{$v['uid']}' and score_type = 3 ");
			// echo $uid_info['sfuid'].'-'.$num['num'].',';
			$uid_income_num = $v['sfuid'].'-'.$v['uid'];

			$income_num[$uid_income_num] = $income_num[$uid_income_num]+$num['num'];	//收入表中的数量(相同的徒弟)

			if($income_num[$uid_income_num] > 2){  //前两单师傅得5元红包
				##师傅的提成计算
				$cost = $v['cost'] <= $v['deal_price'] ? $v['cost'] : $v['deal_price']; //商品价格
				$predict = $cost * $v['rating']/100 * parent::PERCENT; //价格*佣金比*固定比例
				$predict = number_format($predict,2) <= 0.01 ? 0.01 : number_format($predict,2);    //最小计0.01
				if(!empty($v['amount'])) $predict = $predict*$v['amount'];
				$score_type = 1;
				$score_info = "获得好友购买奖励";
			}elseif($income_num[$uid_income_num] <= 2){
				$predict = 5;
				$score_type = 3;
				$score_info = "获得好友购买奖励";
			}
			$str .= "(1,'{$v['sfuid']}','{$v['order_id']}',{$score_type},'{$v['uid']}','{$score_info}',$predict),";
			$str2 .= "('{$v['sfuid']}','{$score_info}'),";
			$str3 .= "('{$v['order_id']}','{$v['sfuid']}',1,'{$v['uid']}',$score_type,'{$score_info}',$predict),";
		}
		$str = rtrim($str,',');
		$str2 = rtrim($str2,',');
		$str3 = rtrim($str3,',');



		if(!empty($str) && !empty($str2))
		{
			M()->startTrans();
			try {
				$sql = "INSERT INTO gw_uid_log (status,uid,order_id,score_type,score_source,score_info,price)
							VALUES $str";
				$sql2 = "INSERT INTO gw_message (uid,content)
							VALUES $str2";
				$sql3 = "INSERT INTO gw_income_log (order_id,uid,status,score_source,score_type,score_info,price)
							VALUES $str3";
				// echo $sql;die;
				M()->query($sql);
				M()->query($sql2);
				M()->query($sql3);
				echo 'ok/';
			} catch (Exception $e) {
				M()->rollback();
			}
			M()->commit();
		}

		#购买人首单奖励
		// $this->firstBuyHandle($order_info);


	}

	//{"order_list":["2930383815453222","2937120419083222"]}
	/**
	 * [buyingFail 购买不成立后的退款]
	 */
	public function buyFail($order_list)
	{

		if(empty($order_list)) return;
		$list = implode(',',$order_list);

		$uid_list = M()->query("select uid,price,status,order_id,score_source,score_type,score_info from gw_uid_log where status in (1,2) and order_id in ({$list}) ",'all');	//根据订单查询相关收入信息(预估,已转余额)
		// D($uid_list);die;

		if(!empty($uid_list))
		{
			$str = '';
			$str2 = '';
			$str3 = '';
			$yugu_list = '';
			$noyugu_list = '';
			$temp = [];
			foreach ($uid_list as $k => $v) {
				$str .= "('{$v['uid']}','好友进行了退款操作,将扣除之前的奖励哦'),";

				if($v['status'] == 2 ){	//已经转到余额的收入
					//合并已转到余额的,该扣除的金额
					if(!array_key_exists($v['uid'], $temp)){	//将同一个uid 所有已经转余额但是要退的订单的,佣金相加
						$a['uid'] = $v['uid'];
						$a['price'] = $v['price'];
						$temp[$v['uid']] = $a;
					}else{
						$temp[$v['uid']]['price'] += $v['price'];
					}
					$noyugu_list .= "'{$v['order_id']}',";
					$status = 4;	//4已转到余额后退单
				}elseif($v['status'] == 1 ){	//预估收入
					$yugu_list .= "'{$v['order_id']}',";
					$status = 3;
				}
				$str3 .= "('{$v['order_id']}','{$v['uid']}',{$status},'{$v['score_source']}','{$v['score_type']}','好友进行了退单,将扣除之前的奖励哦',{$v['price']}),";
			}
			// D($temp);die;
			foreach($temp as $kk => $vv){
				$str2 .= " WHEN '{$vv['uid']}' THEN {$vv['price']} ";	//拼接所有用户扣余额的sql
			}
			// D($str2);die;
			$yugu_list = rtrim($yugu_list,',');
			$noyugu_list = rtrim($noyugu_list,',');
			// D($yugu_list);die;
			// D($noyugu_list);die;
			$str = rtrim($str,',');
			$str3 = rtrim($str3,',');
			M()->startTrans();
			try {
				if(!empty($yugu_list)){
					//根据id,update 每个用户的price
					$sql = "UPDATE gw_uid_log
								SET status = 3 , score_info = '好友进行了退单,将扣除之前的奖励哦'
								WHERE status = 1 AND order_id IN ({$yugu_list})
							";
					M()->query($sql);
				}

				if(!empty($noyugu_list)){
					// echo 123;die;
					//根据id,update 每个用户的price
					$sql5 = "UPDATE gw_uid_log
								SET status = 4 , score_info = '好友进行了退单,将扣除之前的奖励哦'
								WHERE status = 2 AND order_id IN ({$noyugu_list})
							";
					M()->query($sql5);
				}

				$sql2 = "INSERT INTO gw_message (uid,content)
								VALUES $str";
				if(!empty($str2)){
					$sql3 = "UPDATE gw_uid
						SET price = price - CASE objectId".
							$str2
					."ELSE 0 END";
					// echo $sql3;
					M()->query($sql3);
				}
				$sql4 = "INSERT INTO gw_income_log (order_id,uid,status,score_source,score_type,score_info,price)
							VALUES $str3";
				// echo $sql;
				// echo $sql2;
				// echo $sql4;die;
				M()->query($sql2);
				M()->query($sql4);
				echo '退单ok';
			} catch (Exception $e) {
				M()->rollback();

			}
			M()->commit();
		}
	}

	public function interface($uid_list = [])
	{
		if(!empty($uid_list))
		{
			$temp = [];
			$uid = '';
			foreach($uid_list as $v) {
				$uid .= "'{$v}',";
			}
			$uid = rtrim($uid,',');
			$code_list = M()->query("select objectId,imei,idfa from gw_uid where objectId in ({$uid}) ",'all');
			foreach ($code_list as $k => $v)
			{
				if(!empty($v['imei']))
					$temp['imei'][$v['objectId']] = $v['imei'];
				else
					$temp['idfa'][$v['objectId']] = $v['idfa'];
			}
			return $temp;
		}
		return false;
	}

	 function firstBuyHandle($order_info)
	{
		// $order_info = M()->query("select distinct(o.order_id) , o.uid , o.cost ,o.deal_price , o.rating,u.sfuid from gw_order o join gw_uid u on o.uid = u.objectId where o.order_id in ('2960718718611576','2973003829045316','2971944413440202') and o.status = 1 ",'all');
		$uids = array_column($order_info,'uid','order_id');
		$temp = $uids;
		$uids = "'".implode("','",$uids)."'";
		$uided = M()->query("SELECT uid FROM gw_uid_log WHERE uid IN ({$uids}) AND score_type = 7",'all');
		$uided = array_column($uided,'uid');	//订单中已经得过首单奖励的uid

		$uidneed = array_diff($temp,$uided);
		// D($uidneed);die;

		//判断订单中是否有首单
		if(empty($uidneed)) return false;
		$uidneed_str = "'".implode("','",$uidneed)."'";


		$udpate_uid_sql = "UPDATE gw_uid set price = price + 2 where objectId in ($uidneed_str)";
		$add_uidlog_sql = 'INSERT INTO gw_uid_log (status,uid,order_id,score_type,score_info,price) VALUES ';
		$add_message_sql = 'INSERT INTO gw_message (uid,content) VALUES ';
		$add_income_sql = 'INSERT INTO gw_income_log (status,uid,order_id,score_type,score_info,price) VALUES ';


		foreach ($uidneed as $k => $v) {
			$add_uidlog_sql .= "(2,'{$v}','{$k}',7,'首单奖励',2),";
			$add_message_sql .= "('{$v}','您收到首单奖励'),";
			$add_income_sql .= "(2,'{$v}','{$k}',7,'首单奖励',2),";
		}

		$add_uidlog_sql = rtrim($add_uidlog_sql,',');
		$add_message_sql = rtrim($add_message_sql,',');
		$add_income_sql = rtrim($add_income_sql,',');
		M()->startTrans();
		try {
			M()->query($udpate_uid_sql);
			M()->query($add_uidlog_sql);
			M()->query($add_message_sql);
			M()->query($add_income_sql);
			echo 'first_ok'.time();
		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();

		// $code_list = $this->interface($order_uids);
		// if($code_list) {
			// HeadacheController::singleCallback($code_list);
		// }
	}
}