<?php
/**
 * 商品展示
 */
class GoodsShowController extends AppController
{
	public $lft;
	public $rgt;
	public $status      = true; 	//启用 redis
	public $expire 		= 60*60*24;	//过期时间
	public $step        = 20;   	//轮播成员数量
	public $time        = 3;    	//轮播频次
	public $ex_len      = 3000; 	//excel商品数量
	public $goods       = [];
	public $cate_goods	= [];
	public $nodes       = [];
	public $son_nodes   = [];
	public $diff		= [];		//与 redis 的差集
	public $intersect 	= [];		//与 redis 的交集
	public $str         = " SELECT a.*,b.title,b.seller_name nick,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM %s a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id";
	public $ref_str     = " SELECT b.* FROM ngw_goods_category_ref a JOIN ngw_goods_info b ON a.num_iid = b.num_iid WHERE a.status = 1 AND a.category_id =  ";


	/**
	 * [cateGoods 同级子分类商品]
	 */
	public function cateGoods()
	{
		$sql = "SELECT * FROM ngw_category WHERE pid = {$this->dparam['cid']}";
		$soncate = M()->query($sql,'all');
		D($soncate);


	}


	//{"cid":"","page_no":"","page_size":"","system":"","user_id":"","type":"","stype":""}
	/**
	 * [show 展示商品]
	 */
	public function showGoods()
	{
		$this->gtype = 1;	//区分淘宝联盟还是 excel
		set_time_limit(0);
		// R()->delLike('lm');
		$this->stypeHandle();
		if(empty($this->dparam['cid']) || empty($this->dparam['page_no']) || empty($this->dparam['page_size'])) info('数据不全',-1);
		$this->cidToLftRgt();
		$this->cidToGoods($this->lftRgtToCid());

		$total  = $this->sortGoods($this->goods);  //排序后的多节点商品
		// $total  = $this->poll($total);
		$count  = count($total);
		$page   = $this->page($total);
		//特殊商品 存入 redis
		$this->stypeAdd($total);
		info(['status'=>1,'msg'=>'操作成功!','data'=>$page,'son_cate'=>$this->son_nodes,'total'=>$count]);
	}


	/**
	 * [showGoodsEx 展示excel商品]
	 */
	public function showGoodsEx()
	{
		$this->gtype = 2;	//区分淘宝联盟还是 excel
		// R()->delLike('ex');die;
		set_time_limit(0);
		$this->stypeHandle();
		if(empty($this->dparam['cname']) || empty($this->dparam['page_no']) || empty($this->dparam['page_size'])) info('数据不全',-1);
		$this->cidToGoodsEx($this->refToCid());
		$total  = $this->sortGoods($this->goods);  //排序后的多节点商品
		// $total  = $this->poll($total);
		$count  = count($total);
		$page   = $this->page($total);
		//特殊商品 存入 redis
		$this->stypeAddEx($total);
		info(['status'=>1,'msg'=>'操作成功!','data'=>$page,'son_cate'=>$this->son_nodes,'total'=>$count]);

	}


	/**
	 * [stypeAdd 大类板块单独存 redis]
	 */
	private function stypeAdd($total)
	{
		if($this->status == false) return;

		//没有特殊类型返回
		if(empty($this->dparam['stype'])) return;

		switch ($this->dparam['stype']) {
			case 1:
				$this->redisToGoods('lm_all',$total);
				break;
			case 2:
				$this->redisToGoods('lm_99',$total);
				break;
		}
	}

	/**
	 * [stypeAdd 大类板块单独存 redis]
	 */
	private function stypeAddEx($total)
	{
		if($this->status == false) return;

		//没有特殊类型返回
		if(empty($this->dparam['stype'])) return;

		switch ($this->dparam['stype']) {
			case 1:
				$this->redisToGoods('ex_all',$total);
				break;
			case 2:
				$this->redisToGoods('ex_99',$total);
				break;
		}
	}


	/**
	 * [stypeHandle 特殊类型处理]
	 */
	private function stypeHandle()
	{
		if($this->status == false) return;
		//没有特殊类型返回
		if(empty($this->dparam['stype'])) return;

		switch ($this->dparam['stype']) {
			case 1://全部商品
				$str = $this->gtype == 1? 'lm_all' : 'ex_all';
				$page = R()->getListPage($str,$this->dparam['page_no'],$this->dparam['page_size']);
				$count = R()->size('lm_all');
				break;

			case 2://9.9
				$str = $this->gtype == 1? 'lm_99' : 'ex_99';
				$page = R()->getListPage($str,$this->dparam['page_no'],$this->dparam['page_size']);
				$count = R()->size($str);
				break;
		}
		if(!empty($page)){
			info(['status'=>1,'msg'=>'操作成功!','data'=>$page,'son_cate'=>$this->son_nodes,'total'=>$count]);
		}

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
		$total      = array_map(function($arr){
									if ($arr['score'] <= 50 && $arr['is_front'] == 0)
										$arr['poll'] = 1;
									else $arr['poll'] = 0;
									return $arr;
								},$total);
		// D($total);die;
		$key        = array_search(1,array_column($total,'poll'));
		$polls      = array_slice($total,$key);
		array_splice($total,$key);
		$nopolls    = $total;
		$polls      = $this->range($polls);
		$total      = array_merge($nopolls,$polls);

		return $total;
	}


	private function range($polls)
	{
		//测试
		// $temp = [];
		// for ($i=0; $i < 100; $i++)
		//  $temp[] = $i;
		// $polls = $temp;

		$index      = time() % (ceil(count($polls) / $this->step) * $this->time);
		$index      = ceil($index/$this->time);
		// echo $index;
		$polls      = array_chunk($polls,$this->step,true);
		$set_frt    = array_slice($polls,$index);
		array_splice($polls,$index);
		$set_bhd    = $polls;
		$set        = array_merge($set_frt,$set_bhd);
		$polls      = [];
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
	// private function specGoods($where)
	// {
	// 	if(!$where) return false;
	// 	$sql = $this->str.$where;
	// 	$spec = M()->query($sql,'all');
	// 	return $spec;
	// }


	/**
	 * [lftRgtToCid  左右值获取节点以下节点]
	 */
	private function lftRgtToCid()
	{
		$sql = " SELECT id,name FROM ngw_category WHERE `left` >= {$this->lft} AND `right` <= {$this->rgt} ";
		$this->nodes = M()->query($sql,'all');

		foreach ($this->nodes as $k => $v) {
			if($v['id'] != $this->dparam['cid'])
				$this->son_nodes[] = ['cid'=>$v['id'],'name'=>$v['name']];
		}
	}


	/**
	 * [refToCid excel分类方式]
	 */
	private function refToCid()
	{
		if(empty($this->dparam['cname'])) info('参数不全',-1);
		if($this->dparam['cname'] == '全部'){
			$sql = " SELECT DISTINCT name FROM ngw_category WHERE taobao_cid IS NOT NULL";
			$this->nodes = M()->query($sql,'all');
		}


	}


	/**
	 * [strNodes 用于拼接的分类cid]
	 */
	private function strNodes()
	{
		$cids = array_column($this->nodes,'id');
		//走redis
		if($this->status === true){
			//查出内存中已有的商品
			$rcids = $this->checkRedisK();
			$this->intersect = array_intersect($cids,$rcids);	// 取出交集
			$this->diff 	= array_diff($cids,$rcids);	// 差集
			$cids = '('.implode(',',$this->diff).')';
		}else{
			$cids = '('.implode(',',$cids).')';
		}
		return $cids;
	}


	/**
	 * [strNodesEx 用于拼接的分类cname]
	 */
	private function strNodesEx()
	{
		$cnames = array_column($this->nodes,'name');
		//走redis
		if($this->status === true){
			//查出内存中已有的商品
			$rcids = $this->checkRedisK();
			$this->intersect = array_intersect($cnames,$rcids);	// 取出交集
			$this->diff 	= array_diff($cnames,$rcids);	// 差集
			$cnames = "('".implode("','",$this->diff)."')";
		}else{
			$cnames = "('".implode("','",$this->diff)."')";
		}
		// D($this->intersect);
		// D($this->diff);
		// echo $cnames;die;
		return $cnames;
	}


	/**
	 * [checkRedisK 获取 redis已有商品]
	 */
	private function checkRedisK()
	{
		$pre = $this->gtype == 1 ? 'lm' : 'ex';
		$rkeys = R()->keys('ex');
		foreach ($rkeys as &$v)
			$v = explode('_',$v)[1];
		return $rkeys;
	}


	/**
	 * [cidToGoods 节点淘宝联盟商品]
	 */
	private function cidToGoods()
	{
		//上架,淘宝联盟商品,不前置
		$str = sprintf($this->str,'ngw_goods_info');
		$str = $str." WHERE a.is_show = 1 AND a.source = 1 AND a.status =1 AND b.category_id in ";
		//查询差集相关的商品
		$sql = $str.$this->strNodes();

		//9.9商品增加价格条件
		if(!empty($this->dparam['stype']) && $this->dparam['stype'] == 2){
			$sql = $sql." AND b.price <19.9 ";
		}

		$temp = M()->query($sql,'all');
		$cidarr = [];
		foreach ($this->nodes as $v)
			$cidarr[$v['id']] = $v['name'];

		foreach ($temp as $v){
			$this->cate_goods["lm_{$v['cid']}_{$cidarr[$v['cid']]}"][] = $v;
			$this->goods[] = $v;
		}
		// D(count($this->goods));die;
		//将redis 中没有的商品分类写入 redis
		if($this->status === true && !empty($this->cate_goods)){
			foreach ($this->cate_goods as $k => $v)
				$this->redisToGoods($k,$v);
		}
		//合并商品数据库+ redis
		if(!empty($this->intersect))
			$this->mergeGoods();
		$this->cateFavRef();
		$this->goods = unique_multidim_array($this->goods,'num_iid');
	}


	/**
	 * [cidToGoodsEx Excel商品]
	 */
	private function cidToGoodsEx()
	{
		//分数高的前3000
		$str = sprintf($this->str,'ngw_goods_info');
		$str = $str." WHERE a.is_show = 1 AND a.source = 0 AND a.status =1 ORDER BY score DESC LIMIT {$this->ex_len}";
		$str = " SELECT  * FROM ({$str}) a WHERE a.category in ";

		$sql = $str.$this->strNodesEx();
		$temp = M()->query($sql,'all');

		//定义excel 存 redis 的名字
		// goods 用于展示
		foreach ($temp as $v){
			$this->cate_goods["ex_{$v['category']}"][] = $v;
			$this->goods[] = $v;
		}
		//将redis 中没有的商品分类写入 redis
		if($this->status === true && !empty($this->cate_goods)){
			foreach ($this->cate_goods as $k => $v)
				$this->redisToGoods($k,$v);
		}
		//合并商品数据库+ redis
		if(!empty($this->intersect))
			$this->mergeGoodsEx();
		$this->goods = unique_multidim_array($this->goods,'num_iid');
	}


	/**
	 * [mergeGoods 合并 redis中交集的商品]
	 */
	private function mergeGoods()
	{
		$rnodes = [];
		foreach ($this->nodes as $k => $v)
			$rnodes[$v['id']] = "lm_{$v['id']}_{$v['name']}_{$v['id']}";
		foreach ($this->intersect as $v) {
			$rngoods = R()->getListPage($rnodes[$v],0,-1);
			$this->goods = array_merge($this->goods,$rngoods);
		}
	}


	/**
	 * [mergeGoods 合并 redis中交集的商品]
	 */
	private function mergeGoodsEx()
	{
		foreach ($this->intersect as $v) {
			$rngoods = R()->getListPage("ex_{$v}",0,-1);
			$this->goods = array_merge($this->goods,$rngoods);
		}
	}


	/**
	 * [sortGoods 按分数排序]
	 */
	private function sortGoods($arr)
	{
		foreach ($arr as $k => $v) {
			$sort[$k]   = $v['score'];
			$front[$k]  = $v['is_front'];
		}
		array_multisort($front,SORT_DESC,$sort,SORT_DESC,$arr);
		return $arr;
	}


	/**
	 * [cateFavRef 关联的其他分类商品]
	 */
	private function cateFavRef()
	{
		$str        = "( {$this->ref_str} {$this->dparam['cid']} )";
		$str        = sprintf($this->str,$str);
		$sql        = $str." WHERE a.is_show = 1 AND a.status = 1 AND a.source = {$this->dparam['type']}";
		$ref_list   = M()->query($sql,'all');

		$this->goods       = array_merge($this->goods,$ref_list);
	}


	/**
	 * [redisToGoods redis取商品]
	 */
	private function redisToGoods($key,$list)
	{
		R()->addListAll($key,$list);
	}


	/**
	 * [cidToLftRgt cid获取对应的左右值]
	 */
	private function cidToLftRgt()
	{
		$sql = " SELECT `left`,`right` FROM ngw_category WHERE id = {$this->dparam['cid']} ";
		$res = M()->query($sql,'single');

		$this->lft = $res['left'];
		$this->rgt = $res['right'];
	}



	//{"user_id":"Nuwd8XEsBs","num_iid":"525103323591"}
	/**
	 * [share 分享成功]
	 */
	public function share()
	{
		if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'] || empty($this->dparam['type']))) info('参数不全',-1);

		(UserRecordController::getObj()) -> shareRecord($this->dparam['user_id'],$this->dparam['num_iid'],$this->dparam['type']);
		info('ok',1);
	}


	//{"user_id":"Nuwd8XEsBs","num_iid":"525103323591","type":"1"}
	/**
	 * [detail 商品详情]
	 */
	public function detail()
	{
		if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'] || empty($this->dparam['type']))) info('参数不全',-1);

		//记录用户点击
		(UserRecordController::getObj()) -> clickRecord($this->dparam['user_id'],$this->dparam['num_iid'],$this->dparam['type']);


		if(!R()->hashFeildExisit('detailLists',$this->dparam['num_iid'])){

			$sql                = " SELECT * FROM ngw_goods_online WHERE num_iid = '{$this->dparam['num_iid']}' ";
			$info               = M()->query($sql,'single');
			empty($info) && info('商品不存在',-1);
			$info['share_url']  = parent::SHARE_URL.$this->dparam['num_iid'];
			R()->hsetnx('detailLists',$this->dparam['num_iid'],$info,$this->expire);

		}

		$info = R()->getHashSingle('detailLists',(string)$this->dparam['num_iid']);
		// D($info);die;
		info('请求成功',1,$info);
	}



	/**
	 * 获取邀请页的三个商品详情
	 */
	public function getApplyGoods(){
		$sql = "SELECT pict_url,price,reduce,price-reduce  as sell_price from ngw_goods_online where top='1' and status ='1' order by reduce/price desc limit 30";
		$data = M()->query($sql,'all');
		if($data){
		//随机产生0-29之间的三个数
		$numbers = range (0,29);
		shuffle ($numbers);
		$export_data=[];
		for($i=0;$i<3;$i++){
			$export_data[$i]=$data[$numbers[$i]];
		}
		info("列出成功",1,$export_data);
		}
		info("列出失败",-1);
	}

	//{"num_iid":"","status":""}
	/**
	 * 商品开关
	 */
	public function goodsSwitch()
	{
		if(!empty($this->dparam['num_iid']) && !empty($this->dparam['status'])){
			M()->query("update ngw_goods_online set status = 2 where num_iid = {$this->dparam['num_iid']}");
			A('Goods:delAllGoods');
			info('请求成功',1,[]);
		}
	}
	public function searchGoods() {
		$parmas = $this->dparam;
		if(empty($parmas['page_no']) || empty($parmas['page_size']) || !isset($parmas['system']) || !isset($parmas['title']))
			info('缺少参数', -1);

		//记录用户搜索
		// (UserRecordController::getObj())->searchRecord($parmas['user_id'],$parmas['title'],$parmas['system']);
		$query = !empty($query) ? : false;
		$type = !isset($parmas['type']) ? '0,1' : $parmas['type'];

	   //优先展示自己的商品
	   $sql = "SELECT num_iid,title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,reduce,volume FROM ngw_goods_online WHERE status = 1 AND store_type IN('{$type}') AND title like '%".formattedData($parmas['title'])."%' LIMIT ";
	   $self = M()->query($sql .= $query ? (($parmas['page_no'] - 1) * $parmas['page_size']).','.$parmas['page_size'] : 3, 'all');
		//当query 为false 或 库里展示商品小于要查询的商品数量时 查询淘宝客商品
		if(!$query || count($self) < $parmas['page_size'])
			$data = (new TaoBaoApiController('23630111', 'd2a2eded0c22d6f69f8aae033f42cdce'))->tbkItemGetRequest($parmas);
		info('ok', 1, [
			'self'           => $self,
			'taobaoGoods'    => isset($data['taobaoGoods']) ? $data['taobaoGoods'] : [],
			'taobaoGoodsSum' => isset($data['sum'])         ? $data['sum']         : 0,
		]);
	}


	/**
	 * [category 首页商品分类]
	 */
	public function category()
	{
		$cates = [
					['name'=>'女装','cid'=>'133','icon_url'=>RES_SITE.'resource/img/category/img_sort_01.png','content'=>'T恤、衬衫、连衣裙'],
					['name'=>'鞋包','cid'=>'134','icon_url'=>RES_SITE.'resource/img/category/img_sort_02.png','content'=>'凉鞋、拖鞋、单鞋'],
					['name'=>'美妆个护','cid'=>'145','icon_url'=>RES_SITE.'resource/img/category/img_sort_03.png','content'=>'保养、护肤'],
					['name'=>'内衣','cid'=>'154','icon_url'=>RES_SITE.'resource/img/category/img_sort_04.png','content'=>'文胸、保暖内衣'],
					['name'=>'男装','cid'=>'','icon_url'=>RES_SITE.'resource/img/category/img_sort_05.png','content'=>'外套、休闲裤、衬衫'],
					['name'=>'衣饰配件','cid'=>'161','icon_url'=>RES_SITE.'resource/img/category/img_sort_06.png','content'=>'裤装、卫衣'],
					['name'=>'母婴亲子','cid'=>'166','icon_url'=>RES_SITE.'resource/img/category/img_sort_07.png','content'=>'婴儿车、奶瓶'],
					['name'=>'家电','cid'=>'172','icon_url'=>RES_SITE.'resource/img/category/img_sort_08.png','content'=>'家电、厨房电器'],
					['name'=>'数码','cid'=>'178','icon_url'=>RES_SITE.'resource/img/category/img_sort_09.png','content'=>'手机、平板电脑'],
					['name'=>'运动','cid'=>'198','icon_url'=>RES_SITE.'resource/img/category/img_sort_10.png','content'=>'健身、户外'],
					['name'=>'游戏动漫','cid'=>'203','icon_url'=>RES_SITE.'resource/img/category/img_sort_11.png','content'=>'桌游、手办'],
					['name'=>'美食','cid'=>'210','icon_url'=>RES_SITE.'resource/img/category/img_sort_12.png','content'=>'休闲零食、茶水饮料'],
					['name'=>'日常家具','cid'=>'221','icon_url'=>RES_SITE.'resource/img/category/img_sort_13.png','content'=>'床上用品、卧室家具'],
					['name'=>'办公学习','cid'=>'230','icon_url'=>RES_SITE.'resource/img/category/img_sort_14.png','content'=>'办公用品、文具'],
		];
		info('ok',1,$cates);
	}


	/**
	 * [exCategory Excel分类]
	 */
	public function categoryEx()
	{
		$sql = "SELECT DISTINCT name cname FROM ngw_category WHERE taobao_cid IS NOT NULL ";
		$cates = M()->query($sql,'all');
		info('ok',1,[['cname'=>'全部']]+$cates);
	}


	/**
	 * [topCategory 顶部首页分类]
	 */
	public function topCategory()
	{
		$sql = "SELECT id cid,name FROM ngw_category WHERE pid = 1";
		$cate = M()->query($sql,'all');
	}


	//{"user_id":"","page_no":"","page_size":""}
	/**
	 * [history 用户浏览记录]
	 */
	public function history()
	{
		if(R()->hashFeildExisit('history_'.$this->dparam['user_id'],'click')){
			$total = array_filter(R()->getHashSingle('history_'.$this->dparam['user_id'],'click'),function($v){
				if($v['type'] == 1) return $v;	//1表示用户没有删的历史记录
			});
			$page   = $this->page($total);
			info(['status'=>1,'msg'=>'操作成功!','data'=>$page]);
		}else{
			info('暂无数据',-1);
		}
	}


	//{"user_id":""}
	/**
	 * [clearHistory 清除历史记录]
	 */
	public function clearHistory()
	{
		if(R()->hashFeildExisit('history_'.$this->dparam['user_id'],'click')){

			$info = array_map(function($v){
				$v['type'] = 2;
				return $v;
			},R()->getHashSingle('history_'.$this->dparam['user_id'],'click'));

			R()->addHashSingle('history_'.$this->dparam['user_id'],'click',$info);
			info('操作成功',1);

		}else{
			info('暂无数据',-1);
		}
	}

	/**
     * 获取分享的商品的详情
     */
    public function getShareDetail()
    {
        $data=A('Goods:getShareDetail',[I('num_iid')]);
        info('请求成功',1,$data);
    }
}