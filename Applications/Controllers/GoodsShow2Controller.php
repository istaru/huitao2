<?php
class GoodsShow2Controller extends AppController
{
	public $lft;
	public $rgt;
	public $status		= true;	//启用 redis
	public $step		= 20;	//轮播成员数量
	public $time		= 3;	//轮播频次
	public $ex_len		= 3000;	//excel商品数量
	public $goods		= [];
	public $nodes		= [];
	public $son_nodes	= [];
	public $str			= " SELECT a.*,b.title,b.volume,b.price,b.reduce,b.deal_price,b.pict_url,b.item_url,b.url FROM %s a JOIN gw_goods_online b ON a.num_iid = b.num_iid ";
	public $ref_str		= " SELECT b.* FROM gw_goods_category_ref a JOIN gw_goods_info b ON a.num_iid = b.num_iid WHERE a.status = 1 AND a.category_id =  ";




	//{"cid":"","page":"","size":"","system":"","user_id":"","type":""}
	/**
	 * [show 展示商品]
	 */
	public function show()
	{
		if(empty($this->dparam['cid']) || empty($this->dparam['page_no']) || empty($this->dparam['page_size'])) info('数据不全',-1);
		$this->cidToLftRgt();

		if($this->dparam['type'] == 1)
			$this->cidToGoods($this->lftRgtToCid());
		else
			$this->cidToGoodsEx($this->lftRgtToCid());

		$total	= $this->sortGoods($this->goods['total']);	//排序后的多节点商品
		$total	= $this->poll($total);

		$page	= $this->page($total);
		info(['status'=>1,'msg'=>'操作成功!','data'=>$page,'son_cate'=>$this->son_nodes]);
	}


	//{"user_id":"Nuwd8XEsBs","num_iid":"525103323591"}
	/**
	 * [detail 商品详情]
	 */
	public function detail()
	{
		if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'])) info('参数不全',-1);

		//记录到用户点击

		if(!R()->hashFeildExisit('detailLists',$this->dparam['num_iid'])){

			$sql				= " SELECT * FROM gw_goods_online WHERE num_iid = {$this->dparam['num_iid']} ";
			$info				= M()->query($sql,'single');
			$info['share_url']	= parent::SHARE_URL;
			R()->hsetnx('detailLists',$this->dparam['num_iid'],$info);

		}

		$info = R()->getHashSingle('detailLists',$this->dparam['num_iid']);
		// D($info);die;
		info('请求成功',1,$info);
	}


	/**
	 * [page 分页]
	 */
	private function page($total)
	{
		$goods = array_slice($total,$this->dparam['page_no']-1,$this->dparam['page_size']);
		return $goods;
	}


	/**
	 * [poll 轮询]
	 */
	private function poll($total)
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


	private function range($polls)
	{
		//测试
		// $temp = [];
		// for ($i=0; $i < 100; $i++)
		// 	$temp[] = $i;
		// $polls = $temp;

		$index		= time() % (ceil(count($polls) / $this->step) * $this->time);
		$index		= ceil($index/$this->time);
		// echo $index;
		$polls		= array_chunk($polls,$this->step,true);
		$set_frt	= array_slice($polls,$index);
		array_splice($polls,$index);
		$set_bhd	= $polls;
		$set		= array_merge($set_frt,$set_bhd);
		$polls		= [];
		foreach ($set as $v) $polls = $polls + $v;
		// echo '<hg>';
		// echo count($set_frt);
		// echo '<hg>';
		// echo count($set_bhd);
		// echo '<hg>';
		// echo count($set);
		// D($polls);die;
		return $polls;
	}


	/**
	 * [specGoods 特殊的商品]
	 */
	private function specGoods($where)
	{
		if(!$where) return false;
		$sql = $this->str.$where;
		$spec = M()->query($sql,'all');
		return $spec;
	}


	/**
	 * [lftRgtToCid  左右值获取节点以下节点]
	 */
	private function lftRgtToCid()
	{
		$sql = " SELECT id,name FROM gw_category WHERE `left` >= {$this->lft} AND `right` <= {$this->rgt} ";
		$this->nodes = M()->query($sql,'all');

		foreach ($this->nodes as $k => $v) {
			if($v['id'] != $this->dparam['cid'])
				$this->son_nodes[] = ['cid'=>$v['id'],'name'=>$v['name']];
		}
	}


	/**
	 * [cidToGoods 节点商品]
	 */
	private function cidToGoods()
	{
		//上架,淘宝联盟商品,不前置
		$str = sprintf($this->str,'gw_goods_info');
		$str = $str." WHERE a.is_show = 1 AND a.source = {$this->dparam['type']} AND a.status =1 AND b.category_id = ";

		$this->goods['total'] = [];
		foreach ($this->nodes as $k => $v) {
			$fun = $this->status === true ? 'redisToGoods' : 'dbToGoods';

			$sql = $str.$v['id'];
			$key = $this->dparam['type'] == 1 ? $v['name'] : 'ex_'.$v['name'];
			$this->goods[$v['name']]	= $this->$fun($key,$v['id'],$sql);
			$this->goods['total']		= array_merge($this->goods['total'],$this->goods[$v['name']]);
		}
		//去重
		$this->goods['total'] = unique_multidim_array($this->goods['total'],'num_iid');
	}


	private function cidToGoodsEx()
	{
		$str = sprintf($this->str,'gw_goods_info');
		$str = $str." WHERE a.is_show = 1 AND a.source = 0 AND a.status =1 ORDER BY score DESC LIMIT {$this->ex_len}";
		$str = " SELECT  * FROM ({$str}) a WHERE a.category_id =";

		$this->goods['total'] = [];
		foreach ($this->nodes as $k => $v) {
			$fun = $this->status === true ? 'redisToGoods' : 'dbToGoods';

			$sql = $str.$v['id'];
			$key = 'ex_'.$v['name'];
			$this->goods[$v['name']]	= $this->$fun($key,$v['id'],$sql);

			$this->goods['total']		= array_merge($this->goods['total'],$this->goods[$v['name']]);
		}
		//去重
		$this->goods['total'] = unique_multidim_array($this->goods['total'],'num_iid');

	}


	/**
	 * [sortGoods 按分数排序]
	 */
	private function sortGoods($arr)
	{
		foreach ($arr as $k => $v) {
			$sort[$k]	= $v['score'];
			$front[$k]	= $v['is_front'];
		}
		array_multisort($front,SORT_DESC,$sort,SORT_DESC,$arr);
		return $arr;
	}


	/**
	 * [dbToGoods 数据库取商品]
	 */
	private function dbToGoods($key,$cid,$sql)
	{
			//联盟商品
			$list		= M()->query($sql,'all');

			//关联的多分类商品
			$str		= "( {$this->ref_str} {$cid} )";
			$str		= sprintf($this->str,$str);
			$sql		= $str." WHERE a.is_show = 1 AND a.status = 1 AND a.source = {$this->dparam['type']}";
			$ref_list	= M()->query($sql,'all');

			$list 		= array_merge($list,$ref_list);

			return $list;
	}


	/**
	 * [redisToGoods redis取商品]
	 */
	private function redisToGoods($key,$cid,$sql)
	{
		if(!R()->exisit($key)){
			$list = $this->dbToGoods($key,$cid,$sql);

			R()->addListAll($key,array_reverse($list));
			R()->setExpire($key,100);

		}
		return R()->getListPage($key,0,-1);

	}


	/**
	 * [cidToLftRgt cid获取对应的左右值]
	 */
	private function cidToLftRgt()
	{
		$sql = " SELECT `left`,`right` FROM gw_category WHERE id = {$this->dparam['cid']} ";
		$res = M()->query($sql,'single');

		$this->lft = $res['left'];
		$this->rgt = $res['right'];
	}

}