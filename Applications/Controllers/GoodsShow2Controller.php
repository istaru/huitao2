<?php
class GoodsShow2Controller extends AppController
{
	public $lft;
	public $rgt;
	public $step		= 20;
	public $goods		= [];
	public $nodes		= [];
	public $son_nodes	= [];
	public $str			= " SELECT a.*,b.title,b.volume,b.price,b.reduce,b.price,b.deal_price,b.pict_url,b.item_url,b.url FROM gw_goods_info a JOIN gw_goods_online b ON a.num_iid = b.num_iid ";


	public function __construct()
	{
		parent::__construct();
		if(empty($this->dparam['cid']) || empty($this->dparam['page']) || empty($this->dparam['size']))
			info('数据不全',-1);
	}


	//{"cid":"","page":"","size":""}
	/**
	 * [show 展示商品]
	 */
	public function show()
	{
		$this->cidToLftRgt();
		$this->cidToGoods($this->lftRgtToCid());
		$total = $this->sortGoods($this->goods['total']);	//排序后的多节点商品
		$total = $this->poll($total);
		D($total);
	}


	/**
	 * [poll 轮询]
	 */
	public function poll($total)
	{
		//取出需要轮询的部分
		$total		= array_map(function($arr){
									if ($arr['score'] <= 50 && $arr['is_front'] == 0)
										$arr['poll'] = 1;
									else $arr['poll'] = 0;
									return $arr;
								},$total);

		$key		= array_search(1,array_column($total,'poll'));
		$polls		= array_slice($total,$key);
		array_splice($total,$key);
		$nopolls	= $total;
		$polls		= $this->range($polls);
		$total		= array_merge($nopolls,$polls);

		return $total;
	}


	public function range($polls)
	{
		// $temp = [];
		// for ($i=0; $i < 100; $i++)
		// 	$temp[] = $i;
		// $polls = $temp;
		$time		= 4;	//频次
		$index		= time() % (ceil(count($polls) / $this->step)*$time);
		$index		= ceil($index/$time);
		echo $index;
		$polls		= array_chunk($polls,$this->step,true);
		$set_frt	= array_slice($polls,$index);
		array_splice($polls,$index);
		$set_bhd	= $polls;
		$set		= $set_frt + $set_bhd;
		$polls		= [];
		foreach ($set as $v) $polls = $polls + $v;
		return $polls;
	}


	/**
	 * [offsetIndex 记录轮询位置]
	 */
	public function offsetIndex()
	{
		$index = 0;
		if(($index = R()->getKV('range')) !== false)	//取出当前位置
			$index = ($len-$this->step)-1 < $index ? 0 : $index + $this->step;

		R()->setKV('range',$index);	//更新当前位置
	}


	/**
	 * [specGoods 特殊的商品]
	 */
	public function specGoods($where)
	{
		if(!$where) return false;
		$sql = $this->str.$where;
		$spec = M()->query($sql,'all');
		return $spec;
	}


	/**
	 * [lftRgtToCid  左右值获取节点以下节点]
	 */
	public function lftRgtToCid()
	{
		$sql = " SELECT id,name FROM gw_category WHERE `left` >= {$this->lft} AND `right` <= {$this->rgt} ";
		$this->nodes = M()->query($sql,'all');

		foreach ($this->nodes as $k => $v) {
			if($v['id'] != $this->dparam['cid'])
				$this->son_nodes[] = $v;
		}
	}


	/**
	 * [cidToGoods 节点商品]
	 */
	public function cidToGoods()
	{
		//上架,淘宝联盟商品,不前置
		$str = $this->str." WHERE a.is_show = 1 AND a.status =1 AND b.category_id = ";
		$this->goods['total'] = [];
		foreach ($this->nodes as $k => $v) {
			//判断redis是否存在该分类
			$sql = $str.$v['id'];
			$this->goods[$v['name']]	= $this->redisToGoods($v['name'],$sql);
			$this->goods['total']		= $this->goods['total'] + $this->redisToGoods($v['name'],$sql);
		}
	}


	/**
	 * [sortGoods 按分数排序]
	 */
	public function sortGoods($arr)
	{
		foreach ($arr as $k => $v) {
			$sort[$k]	= $v['score'];
			$front[$k]	= $v['is_front'];
		}
		array_multisort($front,SORT_DESC,$sort,SORT_DESC,$arr);
		return $arr;
	}


	/**
	 * [redisToGoods redis取商品]
	 */
	public function redisToGoods($key,$sql)
	{
		if(!R()->exisit($key)){
			$list = M()->query($sql,'all');
			R()->addListAll($key,array_reverse($list));
			R()->setExpire($key,100);
		}
		return R()->getListPage($key,0,-1);
	}


	/**
	 * [cidToLftRgt cid获取对应的左右值]
	 */
	public function cidToLftRgt()
	{
		$sql = " SELECT `left`,`right` FROM gw_category WHERE id = {$this->dparam['cid']} ";
		$res = M()->query($sql,'single');

		$this->lft = $res['left'];
		$this->rgt = $res['right'];
	}

}