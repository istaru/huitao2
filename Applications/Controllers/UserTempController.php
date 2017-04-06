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
	public function clickRecord($uid,$numid)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		$this->type = 'click';

		if(!$this->status){
			$this->commit($uid,$numid,$count);
			return;
		}

		if(R()->hashFeildExisit($uid,$this->type)){
				$data = $this->update($uid,$numid);
		}else{
			$data = $this->goodInfo($numid) + [$this->type => 1];
			R()->hsetnx($uid,$this->type,[$numid => $data],$this->expire);
		}
	}


	/**
	 * [shareRecord description]
	 */
	public function shareRecord($uid,$numid)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		$this->type = 'share';

		if(!$this->status){
			$this->commit($uid,$numid,$count);
			return;
		}

		if(R()->hashFeildExisit($uid,$this->type)){
				$data = $this->update($uid,$numid,$this->type);
		}else{
			$data = $this->goodInfo($numid) + [$this->type => 1];
			R()->hsetnx($uid,$this->type,[$numid => $data],$this->expire);
		}
	}


	/**
	 * [commitRecord 提交]
	 */
	public function commit($uid,$numid)
	{
		echo 'commit';
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
		if(array_key_exists($numid,$info)){
			$info[$numid][$this->type] = $this->ckClickCount($uid,$numid,$info[$numid][$this->type]);
		}else{
			$info[$numid] = $this->goodInfo($numid) + [$this->type => 1];
		}
		R()->addHashSingle($uid,$this->type,$info);
	}


	private function ckClickCount($uid,$numid,$num)
	{
		if($num < $this->count){
			return $num + 1;
		}else{
			$this->commit($uid,$numid,$count);
        	return 1;
		}
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