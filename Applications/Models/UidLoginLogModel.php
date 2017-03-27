<?php

class UidLoginLogModel
{
	public function addUidLoginLog($data,$status=true)
	{
		if(empty($data))
			info('数据有误',-1);
		// $login_data = $this->filterData($data);
		$data['input_time'] = time();
		$data['report_date'] = date('Y-m-d',time());
		return M('uid_login_log')->add($data,$status);
	}


	/**
	 * [filterData 数据过滤]
	 */
	public function filterData($data)
	{
		if(is_array($data))
		{
			$field_list = M('uid_login_log')->getTableFields();
			foreach ($data as $k => &$v)
			{
				if(!in_array($k,$field_list)) unset($data[$k]);
			}
		}
		// D($data);die;
		return $data;
	}
}