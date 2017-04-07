<?php
class UserTempController
{
	public $status	= true;
	public $count	= 10;
	public $type	= null;
	public $expire 	= 3600 * 24 * 7;
	private static $behaviour;


	private function __construct(){
	}


	public static function getObj(){
		if(!(self::$behaviour instanceof self))
			self::$behaviour = new self;
		return self::$behaviour;
	}


	//{"user_id":"","num_iid":""}
	/**
	 * [click 用户点击]
	 */
	public function clickRecord($uid,$numid,$system)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		$this->type = 'click';

		$this->commit($uid,$numid,$system);

		if(!$this->status) return;

		if(R()->hashFeildExisit($uid,$this->type))
			$data = $this->update($uid,$numid);
		else
			R()->hsetnx($uid,$this->type,[$numid => $this->goodInfo($numid)],$this->expire);

	}


	/**
	 * [shareRecord description]
	 */
	public function shareRecord($uid,$numid,$system)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		$this->type = 'share';

		$this->commit($uid,$numid,$system);

		if(!$this->status) return;

		if(R()->hashFeildExisit($uid,$this->type))
			$data = $this->update($uid,$numid,$this->type);
		else
			R()->hsetnx($uid,$this->type,[$numid => $this->goodInfo($numid)],$this->expire);

	}


	public function searchRecord($uid,$content,$system)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		$this->type = 'search';

		$this->commit($uid,$content,$system);
	}


	public function commit($uid,$content,$system)
	{
		R()->addListSingle($this->type,['uid'=>$uid,'content'=>$content,'type'=>$system]);
	}


	private function goodInfo($numid)
	{
		$sql = "SELECT title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,reduce,volume FROM gw_goods_online WHERE num_iid = {$numid}";
		$info = M()->query($sql,'single');
		// D($info);die;
		if(empty($info)) info('商品不存在!',-1);
		return $info;
	}


	private function update($uid,$numid)
	{
		$info = $this->ckGoodsCount(R()->getHashSingle($uid,$this->type));
		R()->addHashSingle($uid,$this->type,$info);
	}


	private function ckGoodsCount($data)
	{
		if(count($data) < $this->count) return $data;
		//删除最早一条
		$data = array_reverse($data,true);
		//提交
		$this->commit($uid,$numid,$count);
		array_pop($data);
		return array_reverse($data,true);
	}



}