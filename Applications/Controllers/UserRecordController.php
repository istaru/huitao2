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
	 * [click 记录用户点击行为]
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

		//用户点击行为额外保存在redis 中作为单个用户的浏览商品记录
		if(R()->hashFeildExisit($this->key,$this->type))
			$data = $this->update();
		else
			R()->hsetnx($this->key,$this->type,[$numid => $this->goodInfo($numid)],$this->expire);

	}


	/**
	 * [shareRecord 记录用户分享行为]
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
	 * [searchRecord 记录用户搜索行为]
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


	/**
	 * [update 取出原来的redis 行为数据并追加]
	 */
	private function update()
	{
		$info = $this->ckGoodsCount(R()->getHashSingle($this->key,$this->type));
		$info[$this->numid] = $this->goodInfo($this->numid);
		R()->addHashSingle($this->key,$this->type,$info);
	}


	/**
	 * [ckGoodsCount 点击行为规则]
	 */
	private function ckGoodsCount($data)
	{
		//小于规定的条数通过
		if(count($data) < $this->count) return $data;

		//大于了删除最早的一条返回
		//删除最早一条
		$data = array_reverse($data,true);
		//提交
		$this->commit($this->uid,$this->numid,$this->count);
		array_pop($data);
		return array_reverse($data,true);
	}



}