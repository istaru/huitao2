<?php
class UserRecordController
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
		$this->uid = $uid;
		$this->numid = $numid;
		$this->system = $system;
		$this->commit(['uid'=>$this->uid,'content'=>$this->numid ,'type'=>$this->system]);

		if(!$this->status) return;

		$this->key = "history_{$uid}";
		if(R()->hashFeildExisit($this->key,$this->type))
			$data = $this->update();
		else
			R()->hsetnx($this->key,$this->type,[$numid => $this->goodInfo($numid)],$this->expire);

	}


	/**
	 * [shareRecord 用户分类]
	 */
	public function shareRecord($uid,$numid,$system,$sharetype)
	{
		if(empty($uid)) info('数据不完整',-1);

		$this->type = 'share';
		$this->uid = $uid;
		$this->numid = $numid;
		$this->system = $system;
		$this->commit(['uid'=>$this->uid,'content'=>$this->numid ,'type'=>$this->system,'share_type'=>$sharetype]);

	}


	/**
	 * [searchRecord 用户搜索]
	 */
	public function searchRecord($uid,$content,$system)
	{
		if(empty($uid) || empty($content)) info('数据不完整',-1);

		$this->type = 'search';
		$this->uid = $uid;
		$this->numid = $content;
		$this->system = $system;
		$this->commit(['uid'=>$this->uid,'content'=>$this->numid ,'type'=>$this->system]);
	}


	public function commit($data)
	{
		R()->addListSingle($this->type,$data);
	}


	private function goodInfo()
	{
		$sql	= "SELECT title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,reduce,volume, 1 type FROM ngw_goods_online WHERE num_iid = {$this->numid}";
		$info 	= M()->query($sql,'single');
		// D($info);die;
		if(empty($info)) info('商品不存在!',-1);
		return $info;
	}


	private function update()
	{
		$info = $this->ckGoodsCount(R()->getHashSingle($this->key,$this->type));
		$info[$this->numid] = $this->goodInfo($this->numid);
		R()->addHashSingle($this->key,$this->type,$info);
	}


	private function ckGoodsCount($data)
	{
		if(count($data) < $this->count) return $data;
		//删除最早一条
		$data = array_reverse($data,true);
		//提交
		$this->commit($this->uid,$this->numid,$count);
		array_pop($data);
		return array_reverse($data,true);
	}



}