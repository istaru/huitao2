<?php
class ScriptController extends Controller
{
	//SELECT * FROM gw_uid_log WHERE createdAt < date_sub(curdate(),interval 7 day)
	public function Income()
	{
		//查出uid_log中所有 当前天数-7天内的 状态是预估的收入记录
		$list = M()->query("SELECT id,uid,price,order_id,score_source,score_type,score_info FROM gw_uid_log WHERE createdAt < date_sub(curdate(),interval 10 day) AND status = 1",'all');
		// D($list);die;
		if(empty($list)) die;
		//遍历结果
		//1.将查到的所有id放入一个变量中用于更新uid_log的状态,
		//2.拼接所有用户objectId 和对应的预收入金额,update到uid中对应的记录
		$str = '';
		$str2 = '';
		$str3 = '';
		$temp = [];
		foreach ($list as $kk => $vv) {
			$id_lists[] = $vv['id'];
			if(array_key_exists($vv['uid'],$temp)){
				$temp[$vv['uid']] = $temp[$vv['uid']] + $vv['price'];
			}else{
				$temp[$vv['uid']] = $vv['price'];
			}
			$str2 .= "('{$vv['uid']}','亲您有一笔预估收入已转到余额'),";
			$str3 .= "('{$vv['order_id']}','{$vv['uid']}',2,'{$vv['score_source']}','{$vv['score_type']}','亲您有一笔预估收入已转到余额',{$vv['price']}),";
		}
		foreach ($temp as $k => $v) {
			$str .= " WHEN '{$k}' THEN {$temp[$k]} ";
		}
		$id_lists = implode(',',$id_lists);
		$str2 = rtrim($str2,',');
		$str3 = rtrim($str3,',');
		M()->startTrans();
		try {
			$sql = "UPDATE gw_uid_log SET status = 2 WHERE id IN ($id_lists)";
			//根据id,update 每个用户的price
			$sql2 = "UPDATE gw_uid
						SET price = price + CASE objectId".
							$str
					."ELSE 0 END";
			$sql3 = "INSERT INTO gw_message (uid,content)
						VALUES $str2";
			$sql4 = "INSERT INTO gw_income_log (order_id,uid,status,score_source,score_type,score_info,price)
						VALUES $str3";
			M()->query($sql);
			M()->query($sql2);
			M()->query($sql3);
			M()->query($sql4);
		} catch (Exception $e) {
			M()->rollback();
			echo $e->getMessage();
			die;
		}
		M()->commit();

		$date = date('y-m-d h:i:s',time());
		echo 'ok'.$date.'\n';
	}


	public function behaviour()
	{
		$len	= 10;
		$sql1	= $this->createSql($this->arrayTotal(R()->getListPage('click',0,$len)),'click');
		$sql2	= $this->createSql($this->arrayTotal(R()->getListPage('share',0,$len)),'share');
		$sql3	= $this->createSql(R()->getListPage('search',0,$len));

		M()->startTrans();
		try {
			M()->query($sql1);
			M()->query($sql2);
			M()->query($sql3);
		} catch (Exception $e) {
			M()->rollback();
		}
		M()->commit();

		//删除 redis相应条数
	}


	private function createSql($arr,$type)
	{
		$date = date('Y-m-d');

		switch ($type) {
			case 'click':
				$str = " INSERT INTO ngw_click_log (uid,num_iid,click,type,report_date) VALUES ";
				break;
			case 'share':
				$str = " INSERT INTO ngw_share_log (uid,num_iid,share,type,report_date) VALUES ";
				break;
			case 'search':
				$str = " INSERT INTO ngw_search_log (uid,search_content,type,report_date) VALUES ";
				break;
		}

		if($type == 'search'){
			foreach ($arr as $k => $v)
				$str .= "('{$v['uid']}','{$v['content']}',{$v['type']},'{$date}'),";
		}else{
			foreach ($arr as $k => $v)
				$str .= "('{$v['uid']}','{$v['num_iid']}',{$v['num']},{$v['type']},'{$date}'),";
		}

		return rtrim($str,',');
	}


	private function arrayTotal($arr)
	{
		$temp = [];
		foreach ($arr as $k => $v) {
			if(!array_key_exists($v['uid'].$v['type'],$temp))
				$temp[$v['uid'].$v['type']] = ['uid'=>$v['uid'],'num_iid'=>$v['content'],'num'=>1,'type'=>$v['type']];
			else
				$temp[$v['uid'].$v['type']]['num'] += 1;
		}
		return $temp;
	}
}