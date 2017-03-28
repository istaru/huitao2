<?php

class VaildModel
{
	public function getVcodeByPhone($phone,$field='*',$status='single')
	{
		if(empty($phone))
			info('手机号不能为空',-1);
		$time = time() - 900;
		return M('vaild_log')->field($field)->where("phone = {$phone} and expire > {$time}")->order('createdAt DESC')->limit(1)->select($status);
	}


	public function addVcode($data,$status = true)
	{
		if(empty($data))
			info('数据有误',-1);

		return M('vaild_log')->add($data,$status);
	}
}