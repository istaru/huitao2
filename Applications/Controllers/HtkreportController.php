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


	/**
	 * [validNewUsers 有效用户--当天或者当天之前有过点击的渠道用户]
	 */
	public function validNewUsers()
	{
		include DIR_LIB.'htreport/keep/validUserKeep.php';
		$keeps = new validUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
		return $keeps  ->  query();
	}


	/**
	 * [channelNewUsers 渠道新增用户]
	 */
	public function newUsers()
	{
		include DIR_LIB.'htreport/keep/newUserKeep.php';
		$keeps = new newUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
		return $keeps  ->  query();
	}


	/**
	 * [newDevices 新增的设备,并绑定淘宝]
	 */
	public function newDevices()
	{
		include DIR_LIB.'htreport/keep/newDeviceUserKeep.php';
		$keeps = new newDeviceUserKeep($_REQUEST['start_time'],$_REQUEST['media_id'],$_REQUEST['type']);
		$sql = $keeps  ->  createSQL();
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