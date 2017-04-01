<?php

class GoodsModel
{
	public $step = 20;
	public $len = 200;
	public function getGoodsDetail($num_iid,$status=true)
	{
		if(!R()->exisit('allDetailLists'))
		{
			$goods_list = M()->query("SELECT sort,id,name,stock, price_pre,price_cut,price,icon_url,goods_url,vocher_url FROM
(
SELECT s.sort,o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM
 (SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s JOIN gw_goods_online o on s.num_iid = o.num_iid ".$this->notInGood()."
)o GROUP BY o.icon_url  ORDER BY sort ASC",'all',$status);
			$arr = [];
			// D($goods_list);die;
			foreach ($goods_list as $k => $v) {
				$arr[$v['id']] = $v;
			}
			// D($arr);die;
			R()->addHashAll('allDetailLists',$arr);
			R()->setExpire('allDetailLists',28800);
		}
		$goods['list'] = R()->getHashSingle('allDetailLists',$num_iid);
		$goods['total'] =R()->getLen('allDetailLists');
		return $goods;
	}


    /**
     * 获取分享商品的详情
     */

    public function getShareDetail($num_iid){
        if($num_iid){
            return   $sharedata= M('goods_online')->field('num_iid,title,pict_url,price,reduce,store_name,volume')->where(['num_iid  ' => ['=',$num_iid]])->order('created_date',1)->limit(0,1)->select();
        }


    }
	/**
	 * [getPageGoodsListSearch 按条件分页查询]
	 */
	public function getPageGoodsListSearch($page=0,$len=10,$where=1,$status=true)
	{
		$limit = " limit {$page} , {$len} ";
		// $goods_list = M()->query("SELECT o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM (SELECT * FROM(SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s ORDER BY sort ASC) s JOIN gw_goods_online o on s.num_iid = o.num_iid where".$where.$limit,'all',$status);
		$sql = "SELECT sort,id,name,stock, price_pre,price_cut,price,icon_url,goods_url,vocher_url FROM
(
SELECT s.sort,o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM
 (SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s JOIN gw_goods_online o on s.num_iid = o.num_iid ".$this->notInGood().' and '.$where."

)o GROUP BY o.icon_url  ORDER BY sort ASC";
		$goods_list = M()->query($sql,'all',$status);
		$goods['list'] = array_slice($goods_list,$page,$len);
		$goods['total'] = count($goods_list);
		return $goods;
	}


	/**
	 * [getGoodsList 获取商品列表]
	 */
	public function getPageGoodsList($page=0,$len=10,$status=true)
	{
		if(!R()->exisit('allGoodsLists'))
		{
			// $goods_list = M()->query("SELECT o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM (SELECT * FROM(SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s ORDER BY sort ASC) s JOIN gw_goods_online o on s.num_iid = o.num_iid",'all',$status);
			$goods_list = M()->query("SELECT sort,id,name,stock, price_pre,price_cut,price,icon_url,goods_url,vocher_url FROM
(
SELECT s.sort,o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM
 (SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s JOIN gw_goods_online o on s.num_iid = o.num_iid
".$this->notInGood()."
)o GROUP BY o.icon_url  ORDER BY sort ASC",'all',$status);

			R()->addListAll('allGoodsLists',array_reverse($goods_list));
			R()->setExpire('allGoodsLists',28800);
		}

		$goods['list'] = R()->getListPage('allGoodsLists',$page,$len);
		$goods['total'] =R()->getLen('allGoodsLists');
		return $goods;
	}

	/**
	 * [getPageGoodsListForType 根据类型查出商品]
	 */
	public function getPageGoodsListForType($page=0,$len=10,$type,$where=1,$status=true)
	{

		empty($type) && info('商品类型不存在',-1);
		if(!R()->exisit($type))
		{

			$goods_list = M()->query("SELECT sort,id,name,stock, price_pre,price_cut,price,icon_url,goods_url,vocher_url FROM
(
SELECT s.sort,o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM
 (SELECT num_iid,(sort % 1000) as sort FROM gw_goods_sort) s JOIN gw_goods_online o on s.num_iid = o.num_iid
".$this->notInGood().' and '.$where."
)o GROUP BY o.icon_url  ORDER BY sort ASC",'all',$status);
			R()->addListAll($type,array_reverse($goods_list));
			R()->setExpire($type,28800);
		}


		if($type == '9.9'){
			$goods_list = $this->rangeList(1,['type'=>'9.9','step'=>$this->step,'len'=>$this->len]);
			$goods['list'] = array_slice($goods_list,$page,$len);
			$goods['total'] = count($goods_list);
		}else{
			$goods['list'] = R()->getListPage($type,$page,$len);
			$goods['total'] =R()->getLen($type);

		}
		return $goods;
	}

	/**
	 * [getPageGoodsListForToday 今日上新]
	 */
	public function getPageGoodsListForToday($page=0,$len=10,$uid='',$status=true)
	{

		if(!R()->exisit('today'))
		{
			$goods_list = M()->query("SELECT sort,id,name,stock, price_pre,price_cut,price,icon_url,goods_url,vocher_url FROM ( SELECT s.sort,o.num_iid as id,o.title as name,o.volume as stock,o.price as price_pre,o.reduce as price_cut,deal_price as price,o.pict_url as icon_url,o.item_url as goods_url,o.url as vocher_url FROM (SELECT * FROM gw_goods_sort where sort > 1000 ORDER BY sort ASC) s JOIN (SELECT * FROM gw_goods_online ORDER BY top desc,created_date DESC )o on s.num_iid = o.num_iid ".$this->notInGood()."  ) o ORDER BY sort ASC",'all',$status);

			R()->addListAll('today',array_reverse($goods_list));
			R()->setExpire('today',36000);
		}

		// if(!empty($uid))
		// 	$goods_list = $this->rangeList(2,['type'=>'today','step'=>$this->step,'len'=>$this->len,'uid'=>$uid]);
		// else
		// 	$goods_list = $this->rangeList(1,['type'=>'today','step'=>$this->step,'len'=>$this->len]);
		//
		$goods_list = $this->rangeList(1,['type'=>'today','step'=>$this->step,'len'=>$this->len]);


		$goods['list'] = array_slice($goods_list,$page,$len);
		$goods['total'] = count($goods_list);
		return $goods;
	}

	/**
	 * [rangeList 排序]
	 */
	private function rangeList($type=1,$param=[])
	{
		if(($index = R()->getKV('listRange')) !== false)	//取出当前位置
			$index = $index;
		else
			$index = 0;


		if($type == 1){

			$range_list = R()->getListPage($param['type'],0,$param['len']);	//取出整个轮询涉及的所有条
			$first_list = array_slice($range_list,$index,$param['step']);	//取出轮询数组
			// shuffle($first_list);	//打乱取出轮询的数组
			array_splice($range_list,$index,$param['step']);	//截取(取出轮询的数组)
			$front_list = array_merge($first_list,$range_list);	//前移的和剩余的拼接
			$last_list = R()->getListPage($param['type'],$param['len']+1,-1);	//len以为还在内存中的取出
			$goods_list = array_merge($front_list,$last_list);

		}elseif($type == 2){

			$range_list = R()->getListPage($param['type'],0,$param['len']);	//取出整个轮询涉及的所有条

			if(R()->hashFeildExisit('usersGoods',$param['uid'])){
				$front_list = R()->getListPage($param['uid'],0,-1);	//取出整个轮询涉及的所有条
			}else{

				$range_list = R()->getListPage($param['type'],0,$param['len']);	//取出整个轮询涉及的所有条
				$first_list = array_slice($range_list,$index,$param['step']);	//取出轮询数组
				// shuffle($first_list);	//打乱取出轮询的数组
				array_splice($range_list,$index,$param['step']);	//截取(取出轮询的数组)
				$front_list = array_merge($first_list,$range_list);	//前移的和剩余的拼接

				file_put_contents(DIR.'/log.txt',json_encode($param['uid'] ));
				R()->addHashSingle('usersGoods',$param['uid'],$front_list);
				R()->setExpire($param['uid'],120);
			}

			// array_splice($range_list,$index,$param['step']);	//截取(取出轮询的数组)
			// $front_list = array_merge($first_list,$range_list);	//前移的和剩余的拼接
			$last_list = R()->getListPage($param['type'],$param['len']+1,-1);	//len以为还在内存中的取出
			$goods_list = array_merge($front_list,$last_list);

		}



		return $goods_list;
	}

	public function offsetIndex()
	{
		if(($index = R()->getKV('listRange')) !== false){	//取出当前位置
			$index = ($this->len-$this->step)-1 < $index ? 0 : $index+$this->step;
		}else{
			$index = 0;
		}
		R()->setKV('listRange',$index);	//更新当前位置
	}

	/**
	 * [notInGood 排除的商品id]
	 */
	private function notInGood()
	{
		$list = M()->query('SELECT o.num_iid FROM (SELECT * FROM gw_goods_sort ORDER BY sort ASC) s JOIN gw_goods_online o on s.num_iid = o.num_iid where  created_date = curdate()   GROUP BY o.title having count(0)>1
	UNION
	SELECT o.num_iid FROM
	(SELECT * FROM gw_goods_sort ORDER BY sort ASC) s JOIN gw_goods_online o on s.num_iid = o.num_iid
	where  created_date = curdate()   GROUP BY o.pict_url having  count(o.pict_url)>1','all');
		if(!empty($list))
		{
			$list = array_column($list, 'num_iid');
			$list = ' WHERE o.status = 1 and o.num_iid not in ('.implode(',',$list).') ';
		}else{
			$list = ' WHERE o.status = 1 ';
		}

		return $list;
	}

	/**
	 * [getTypes 获取商品类型]
	 */
	public function getTypes()
	{
		return M()->query('select pid as id ,name from gw_category  where me is not null group by name order by pid asc','all');
	}

	/**
	 * [delAllGoods 删除redis中所有商品相关field]
	 */
	public function delAllGoods()
	{
		$fieldList = ['1','2','3','4','5','6','7','8','9','10','today','9.9','allDetailLists','allGoodsLists','usersGoods'];

		foreach ($fieldList as $k => $v) {
			R()->delFeild($v);
		}
	}
}