<?php
/*
提现模型
 */
class PnowModel
{
	//通过uid获取提现信息
	public function getPnowInfo($where='',$field='*',$status=true)
	{
		if(empty($where))
			info('where不能为空',-1);
		$p_info = M('pnow')->field($field)->where($where)->select($status);

		return $p_info;
	}

	//更新pnow表数据
	public function updatePnow($where,$data)
	{
		// echo $p_up = M('pnow')->where($where)->save($data,false);die;

		$p_up = M('pnow')->where($where)->save($data);
		return $p_up;
	}

	//新增提现申请
	public function addPnow($data,$status=true)
	{
		if(empty($data))
			info('数据有误',-1);
		// echo M('pnow')->add($pnow_data,false);die;

		return M('pnow')->add($data,$status);
	}


}