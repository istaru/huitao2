<?php
/*
app用户模型
 */
class UidModel
{
	public function getInfo($where=1,$field='*',$status='single')
	{

		if(empty($where))
			info('where不能为空',-1);

		$u_info = M('uid')->field($field)->where($where)->limit(1)->select($status);

		return $u_info;
	}

	public function addUid($data,$status=true)
	{
		if(empty($data))
			info('数据有误',-1);
		$uid_data = $this->filterData($data);
		$uid_data['objectId'] = $this->createRandomOnlyStr();
		$uid_data['nickname'] = $uid_data['objectId'];
		$uid_data['report_date'] = date('Y-m-d',time());
		$uid_data['head_img'] = RES_SITE."shoppingResource/head/".rand(1,2).".jpg";
		$num = M()->query('select id from ngw_uid order by id desc limit 1');
		$uid_data['Invitation_code'] = generateInvitationCode($num['id']);
		if(M('uid')->add($uid_data,$status))
			return $uid_data['objectId'];
		else
			info('注册失败',-1);
	}

	public function updateUid($where,$data,$status=true)
	{
		if(empty($where))
			info('where不能为空',-1);
		// $uid_data = $this->filterData($data);
		if(!empty($data['phone'])) unset($data['phone']);
		$uid_data = $data;

		return M('uid')->where($where)->save($uid_data,$status);
	}

	/**
	 * [filterData 数据过滤]
	 */
	protected function filterData($data)
	{
		if(is_array($data))
		{
			$field_list = M('uid')->getTableFields();
			foreach ($data as $k => &$v)
			{
				if(!in_array($k,$field_list)) unset($data[$k]);
			}
		}
		return $data;
	}


	//生成不重复随机字串
	protected function createRandomOnlyStr(){
		$i = 0;
		do {
			$add = null;
			if($i>3){
				$output = randstr(11,'MIX');
			}else{
				$output = randstr(10,'MIX');
			}
			$ck = M()->query("SELECT count(0) as count FROM ngw_uid WHERE `objectId` = '".$output."'",'single');
			$i++;
		} while ((int)$ck['count'] > 0);
		return $output;
	}


}