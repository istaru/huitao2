<?php

class DidLogModel
{
	public function getDidLogInfo($where,$field='*',$status=true)
	{
		if(empty($where))
			info('where不能为空',-1);

		$dlog_info = M('did_log')->field($field)->where($where)->select($status);
		return $dlog_info;
	}

	public function addDidLog($data,$status=true)
	{
		if(empty($data))
			info('数据有误',-1);
		$didLog_data = $this->filterData($data);

		return M('did_log')->add($didLog_data,$status);
	}

	/**
	 * [filterData 数据过滤]
	 */
	public function filterData($data)
	{
		if(is_array($data))
		{
			$field_list = M('did_log')->getTableFields();
			foreach ($data as $k => &$v)
			{
				if(!in_array($k,$field_list)) unset($data[$k]);
			}
		}
		return $data;
	}
}