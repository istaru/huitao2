
<?php

// class HtreportController extends  HtController
class HtreportController

{

	/**
	 * [trackUserInfoReport 渠道效果报表]
	 */
	public function trackUserInfoReport ()
	{

		// D($_REQUEST);die;
		##内部主句
		$inner_main_sql = $this->mainTrackReportSql();

		##不包含在内的订单
		$notin_order_sql = $this->notInOrderIdSql();

		##师傅主句
		$main_sql = "
			SELECT t.uid,sf.fee,sf.benifit FROM gw_tracking t
				LEFT JOIN  (
						-- 师傅
						SELECT uid,sum(fee) AS fee,sum(benifit) AS benifit FROM gw_shopping_log
						WHERE uid IN
						({$inner_main_sql})
						AND order_id NOT IN ({$notin_order_sql})
						GROUP BY uid

				) sf    ON t.uid = sf.uid WHERE t.uid IS NOT NULL
				";

		##徒弟主句
		$td_main_sql = "
					-- 徒弟
					SELECT uid,sum(fee) AS fee,sum(benifit) AS benifit FROM gw_shopping_log
					WHERE uid IN
					(SELECT objectId FROM gw_uid WHERE sfuid IN ({$inner_main_sql}) )
					AND order_id not IN ({$notin_order_sql})
		";


		//渠道
		if(!empty($_REQUEST['media_id'])) $main_sql .= "AND t.source = '{$_REQUEST['media_id']}' ";
		// echo $main_sql;die;
		//时间
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time']) ){
			$main_sql .= " AND t.report_date BETWEEN '{$_REQUEST['start_time']}' AND '{$_REQUEST['end_time']}'";
			$td_main_sql .= " AND report_date BETWEEN '{$_REQUEST['start_time']}' AND '{$_REQUEST['end_time']}'";
		}elseif(!empty($_REQUEST['start_time'])){
			$main_sql .= " AND t.report_date > '{$_REQUEST['start_time']}'";
			$td_main_sql .= " AND report_date > '{$_REQUEST['start_time']}'";
		}elseif(!empty($_REQUEST['end_time'])){
			$main_sql .= " AND t.report_date < '{$_REQUEST['end_time']}'";
			$td_main_sql .= " AND report_date < '{$_REQUEST['end_time']}'";
		}



		$total_sql = "
SELECT a.* FROM (
	SELECT a.*,b.td_num,sum(b.td_fee) AS td_fee,sum(b.td_benifit) AS td_benifit FROM(
		{$main_sql}
	) a
	LEFT JOIN
	(
			SELECT count(a.uid) AS td_num,sum(a.fee) AS td_fee,sum(a.benifit) AS td_benifit,b.sfuid FROM (
					-- 徒弟
					{$td_main_sql}
					GROUP BY uid
			) a
			LEFT JOIN
			(
				-- 关系
				SELECT sfuid,objectId FROM gw_uid WHERE sfuid IN ({$inner_main_sql})
			) b
			ON a.uid = b.objectId GROUP BY b.sfuid

	) b ON b.sfuid = a.uid GROUP BY a.uid
) a
";
// echo $total_sql;die();

		if(!empty($_REQUEST['order']) && !empty($_REQUEST['order_param'])){
			$total_sql .= " ORDER BY {$_REQUEST['order_param']} {$_REQUEST['order']} ";
		}
		// echo $total_sql;die;

		$tack_user_info = M()->query($total_sql,'all');
		// D($tack_user_info);die;
		foreach ($tack_user_info as $k => &$v) {
			$v['fee_total'] = $v['fee'] + $v['td_fee'] > 0 ? $v['fee'] + $v['td_fee'] : '';
			$v['benifit_total'] = $v['benifit'] + $v['td_benifit'] > 0 ? $v['benifit'] + $v['td_benifit'] : '' ;
		}
		// D($tack_user_info);die;
		if(count($tack_user_info) > 0){
			//总用户下单额
			$all_fee_total = array_sum(array_column($tack_user_info, 'fee')) + array_sum(array_column($tack_user_info, 'td_fee'));
			//总用户下单利润
			$all_benifit_total = array_sum(array_column($tack_user_info, 'benifit')) + array_sum(array_column($tack_user_info, 'td_benifit'));
			//推广总人数
			$all_td_num = array_sum(array_column($tack_user_info, 'td_num'));
			//总人数
			$all_user_num = count($tack_user_info) + $all_td_num;
			//人均下单额
			$average_fee = number_format($all_fee_total/$all_user_num,2);
			//人均下单利润
			$average_benifit = number_format($all_benifit_total/$all_user_num,2);
			//人均推广率
			$average_track = number_format($all_td_num/$all_user_num,2);

			$info['total_data'] = compact('all_user_num','all_fee_total','all_benifit_total','all_td_num','average_fee','average_benifit','average_track');
			// D($track_total_data);

			$info['total'] = count($tack_user_info);

			if(!empty($_REQUEST['page_no']) && !empty($_REQUEST['page_size'])){
				$page = ($_REQUEST['page_no'] - 1) * $_REQUEST['page_size'] ;
				// $size = $page + $_REQUEST['page_size'] - 1;	//redis分页
				$size = $_REQUEST['page_size'];
				$info['list'] = array_slice($tack_user_info,$page,$size);

			}else{
				$info['list'] = $tack_user_info;
			}
			// D($info);die;
			info($info);
		}
		info(['status'=>-1]);

	}



	/**
	 * [mainTrackReportSql description]
	 * @return [type]        [1注册激活,2下单激活]
	 */
	private function mainTrackReportSql()
	{
		$sql = "SELECT DISTINCT(uid) FROM gw_tracking  WHERE `uid` IS NOT NULL ";
		return $sql;
	}


	private function notInOrderIdSql()
	{
		//退单的订单ID
		$sql = "SELECT order_id FROM gw_shopping_log WHERE order_status = 5";
		return $sql;
	}

	/**
	 * [channelOneReport 各渠道折线图]
	 */
	public function channelOneReport()
	{

		$inner_main_sql = "SELECT DISTINCT(uid) FROM gw_tracking  WHERE uid IS NOT NULL ";

		//渠道
		if(!empty($_REQUEST['media_id']) && $_REQUEST['media_id'] != 'teyao'){
			$inner_main_sql .= " AND source = '{$_REQUEST['media_id']}' ";

			$str = M()->query("select str from gw_track_batch where batch = '002'",'single');
			$inner_main_sql .= $str['str'];



			$inner_main_sql2 = "SELECT objectId FROM gw_uid WHERE objectId IN ({$inner_main_sql}) AND type = {$_REQUEST['type']}";
		}

		//特邀
		if(!empty($_REQUEST['media_id']) && $_REQUEST['media_id'] == 'teyao'){
			$inner_main_sql2 = " SELECT objectId FROM gw_uid WHERE power = 2
			";
		}


		//时间
		if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time']) ){
			//生成日期数组
			$dt_list = $this->prDates($_REQUEST['start_time'],$_REQUEST['end_time']);
			$dt_where = " AND report_date BETWEEN '{$_REQUEST['start_time']}' AND '{$_REQUEST['end_time']}'";
		}elseif(!empty($_REQUEST['start_time'])){
			$dt_where = " AND report_date > '{$_REQUEST['start_time']}'";
		}elseif(!empty($_REQUEST['end_time'])){
			$dt_where = " AND report_date < '{$_REQUEST['end_time']}'";
		}else{
			$dt_where = '';
		}


		$sf_sql = "
-- 师傅
SELECT report_date,sum(fee) AS fee,sum(benifit) AS benifit FROM gw_shopping_log
WHERE uid IN
({$inner_main_sql2})
AND order_id NOT IN (".$this->notInOrderIdSql().")
".$dt_where."
GROUP BY report_date
		";
		$td_sql = "
-- 徒弟
SELECT report_date,sum(fee) AS td_fee,sum(benifit) AS td_benifit FROM gw_shopping_log
WHERE uid IN
(SELECT objectId FROM gw_uid WHERE sfuid IN (
	{$inner_main_sql2})
)
AND order_id NOT IN (".$this->notInOrderIdSql().")
".$dt_where."
GROUP BY report_date
		";
//查询每日分享数
$share_sql = "SELECT report_date,count(id) AS share FROM gw_share_log WHERE type = {$_REQUEST['type']} {$dt_where} AND uid in ({$inner_main_sql2}) GROUP BY report_date ";
//查询每日分享率(去重分享数/去重登入数)
$share_percent_sql = "SELECT a.report_date,left((a.num/b.num*100),5) as s_percent FROM
(select report_date,count(DISTINCT(uid)) as num from gw_share_log where type = {$_REQUEST['type']} {$dt_where} AND uid in ({$inner_main_sql2}) GROUP BY report_date) a
JOIN
(select report_date,count(DISTINCT(uid)) as num from gw_uid_login_log where type = {$_REQUEST['type']} {$dt_where}  AND uid in ({$inner_main_sql2})  GROUP BY report_date) b
ON a.report_date = b.report_date";
// echo $share_percent_sql;die;
$type = $_REQUEST['type'] == '0' ? '2' : $_REQUEST['type'] ;
//查询每日留存数
$keep_sql = "
select a.report_date,(a.uid - b.uid) as keep from (

		select report_date,count(DISTINCT(uid)) uid from gw_uid_login_log where uid in (

					select DISTINCT(uid) from gw_tracking where system = {$type} and source = '{$_REQUEST['media_id']}' and uid is not null

		) and type = {$_REQUEST['type']} {$dt_where} group by report_date

) a
join
(
		select count(DISTINCT(a.uid)) uid,a.report_date from gw_uid_login_log a
		join
		(
			select uid,report_date from gw_tracking where system = {$type} and source = '{$_REQUEST['media_id']}' and uid is not null

		)b on a.report_date = b.report_date and a.uid = b.uid where type = {$_REQUEST['type']} and a.report_date between '{$_REQUEST['start_time']}' and '{$_REQUEST['end_time']}' GROUP BY a.report_date

) b
on a.report_date = b.report_date
";
echo $keep_sql;die;


//推广新增
	$track_num_sql = "SELECT report_date,count(distinct(uid)) as track_num from gw_tracking where  uid IS NOT NULL and source = '{$_REQUEST['media_id']}' and system = {$type} {$dt_where} GROUP BY report_date";
//邀请新增
$invatation_add_sql = "SELECT report_date,count(id) as invatation_num FROM gw_uid WHERE sfuid IN ({$inner_main_sql2}) {$dt_where} GROUP BY report_date";


//有效用户(新增用户的点击数)
$user_click_num_sql = "select report_date,count(DISTINCT(uid)) as click_num from gw_click_log where uid in ((select DISTINCT(uid) from gw_tracking where uid IS NOT NULL and uid in (select distinct(uid) from gw_taobao_log) and system = {$type}  {$dt_where} )) {$dt_where} GROUP BY report_date
";
echo $user_click_num_sql;die;
// echo $user_click_num_sql;die;
		$ch_time_sf = $this->hdArr(M()->query($sf_sql,true));
		$ch_time_td = $this->hdArr(M()->query($td_sql,true));
		$ch_time_share = $this->hdArr(M()->query($share_sql,true));
		$ch_time_share_percent = $this->hdArr(M()->query($share_percent_sql,true));
		$ch_time_keep = $this->hdArr(M()->query($keep_sql,true));
		$ch_time_track_num = $_REQUEST['media_id'] != 'teyao' ? $this->hdArr(M()->query($track_num_sql,true)) : [] ;
		$ch_time_in_add = $_REQUEST['media_id'] != 'teyao' ? $this->hdArr(M()->query($invatation_add_sql,true)) : [] ;
		$ch_time_click_num = $_REQUEST['media_id'] != 'teyao' ? $this->hdArr(M()->query($user_click_num_sql,true)) : [] ;


		$basic_list = ['fee'=>'','benifit'=>'','td_fee'=>'','td_benifit'=>'','share'=>'','s_percent'=>'','keep'=>'','track_num'=>'','click_num'=>'','invatation_num'=>''];

		$mg_list = array_merge_recursive($ch_time_sf,$ch_time_td,$ch_time_share,$ch_time_share_percent,$ch_time_keep,$ch_time_track_num,$ch_time_click_num,$ch_time_in_add);

		foreach ($dt_list as $k => $v) {
			if(array_key_exists($v,$mg_list)){
				$temp[$v] = array_merge($basic_list,$mg_list[$v]);
			}else{
				$temp[$v] = $basic_list;
			}
		}
		// D($temp);
		foreach ($temp as $kk => $vv) {
			$vv['date'] = $kk;
			$_temp[] = $vv;
		}
		// D($_temp);

		info($_temp);

	}

	public function channelOneKeepLine()
	{
		$start = strtotime($_REQUEST['start_time']);
		$end = date('Y-m-d',strtotime('+7 day',$start));
		$sql = "
select report_date as date,count(DISTINCT(uid)) as keep from gw_uid_login_log where uid in (

	select uid from gw_tracking where uid is not null and system = 1 and source = 'youmi' and report_date = '{$_REQUEST['start_time']}'

) and report_date between '{$_REQUEST['start_time']}' and '{$end}' and type =1 GROUP BY report_date
		";
		$data = M()->query($sql,'all');
		// D($data);die;
		info($data);
	}



	private function prDates($start,$end)
	{
		$dt_start = strtotime($start);
		$dt_end = strtotime($end);
		while ($dt_start<=$dt_end){
			$dt_list[] = date('Y-m-d',$dt_start);
			$dt_start = strtotime('+1 day',$dt_start);
		}
		return $dt_list;
	}


	private function hdArr($arr)
	{
		if(!empty($arr)){
			foreach ($arr as $k => $v) {
				$_arr[$v['report_date']] = $v;
				unset($_arr[$v['report_date']]['report_date']);
			}
			return $_arr;
		}else{
			return [];
		}

	}

}
?>
