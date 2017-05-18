<?php
/**
 * ##生成留存用户折线图数据##
 *
 * 不区分渠道所有点击的新增用户
 * 划分渠道新增用户
 * 新增的设备,并绑定淘宝
 */
class HtkreportController
{

	// select  report_date,count(DISTINCT(uid)) as valid_num  from  ngw_uid_login_log   where uid in ( select DISTINCT(a.uid) from ngw_click_log a join  ngw_tracking b on a.uid = b.uid where a.report_date = '2007-1-1'  and b.source = '1' )   and report_date between '2007-1-1' and '2007-01-08'   GROUP BY report_date
	/**
	 * [validNewUsers 有效用户--当天或者当天之前有过点击的渠道用户]
	 */
	public function validNewUsers()
	{
		include DIR_LIB.'htreport/keep/validUserKeep.php';
		$keeps = new validUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
		echo $sql;
		return $keeps  ->  query();
	}


	// select  report_date,count(DISTINCT(uid)) as new_num  from  ngw_uid_login_log   where uid in ( select DISTINCT(a.uid) from ngw_taobao_log a join  ngw_tracking b on a.uid = b.uid where b.report_date = '2007-1-1' and b.source = '1' )   and report_date between '2007-1-1' and '2007-01-08'   GROUP BY report_date
	/**
	 * [channelNewUsers 渠道新增用户]
	 */
	public function newUsers()
	{
		include DIR_LIB.'htreport/keep/newUserKeep.php';
		$keeps = new newUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
		echo $sql;
		return $keeps  ->  query();
	}


	// select  report_date,count(DISTINCT(uid)) as device_num  from  ngw_uid_login_log   where uid in (  select DISTINCT(a.uid) from (select b.uid from ngw_did a join ngw_taobao_log b on a.id = b.did_id where a.report_date = '2007-1-1') a join  ngw_tracking b on a.uid = b.uid where b.report_date = '2007-1-1' and b.source = '1' )   and report_date between '2007-1-1' and '2007-01-08'   GROUP BY report_date
	/**
	 * [newDevices 新增的设备,并绑定淘宝]
	 */
	public function newDevices()
	{
		include DIR_LIB.'htreport/keep/newDeviceUserKeep.php';
		$keeps = new newDeviceUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
		echo $sql;
		return $keeps  ->  query();
	}


	/**
	 * [info 前端交互]
	 */
	public function info()
	{

		$all 		= $this->hdArr($this->validNewUsers());
		$channel 	= $this->hdArr($this->newUsers());
		$devices 	= $this->hdArr($this->newDevices());

		$data = array_merge_recursive($all,$channel,$devices);
		info($data);
	}


	/**
	 * [hdArr 数组处理]
	 */
	private function hdArr($arr)
	{
		if(!empty($arr)){
			foreach ($arr as $k => $v) {
				$_arr[$v['report_date']] = $v;
				unset($_arr[$v['report_date']]['report_date']);
				unset($_arr[$v['report_date']]['week']);
			}
			return $_arr;
		}else{
			return [];
		}
	}








}