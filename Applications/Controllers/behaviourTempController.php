<?php
class BehaviourTempController
{
	public $status  = true;
	public $count   = 10;   //记录商品数量
	private static $behaviour;


	private function __construct(){
	}


	public static function getObj(){
		if(!(self::$behaviour instanceof self))
			self::$behaviour = new self;
		return self::$behaviour;
	}


	//{"user_id":"","num_iid"}
	/**
	 * [click 用户点击]
	 */
	public function clickRecord($uid,$numid)
	{
		if(empty($uid) || empty($numid)) info('数据不完整',-1);

		if(R()->hashFeildExisit($uid,'click')){
				$data = $this->update($uid,$numid);
		}else{
			$data = $this->goodInfo($numid) + ['click_num' => 1];
			R()->hsetnx($uid,'click',[$numid => $data]);
		}
	}


	private function goodInfo($numid)
	{
		$sql = "SELECT title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,reduce,volume FROM gw_goods_online WHERE num_iid = {$numid}";
		$info = M()->query($sql,'single');
		// D($info);die;
		if(empty($info)) info('商品不存在!',-1);
		return $info;
	}


	private function update($uid,$numid,$type='click')
	{
		$info = $this->ckGoodsCount(R()->getHashSingle($uid,$type));
		if(array_key_exists($numid,$info)){
			$info[$numid]['click_num'] = $this->ckClickCount($uid,$numid,$info[$numid]['click_num']);
		}else{
			$info[$numid] = $this->goodInfo($numid) + ['click_num' => 1];
		}
		R()->addHashSingle($uid,'click',$info);
	}


	private function ckClickCount($uid,$numid,$click_num)
	{
		if($click_num < 10){
			return $click_num + 1;
		}else{
			// $record = new RedisCacheController;
			//$record->insertUidClickData(["num_iid"=>$numid,"uid"=>$uid,"click"=>$click_num+1,"type"=>2,"report_date"=>date('Y-m-d')]);
        	return 1;
		}
	}


	private function ckGoodsCount($data)
	{
		if(count($data) < 10) return $data;

		//删除最早一条
		$data = array_reverse($data,true);
		array_pop($data);
		return array_reverse($data,true);
	}


	private function clickHandle($uid,$numid)
	{
	}
}