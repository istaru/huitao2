<?php
/**
 * 商品展示
 */
class GoodsShowController extends AppController
{
    public $lft;
    public $rgt;
    public $status      = true;     //启用 redis
    public $expire      = 60*60*24; //过期时间
    public $step        = 20;       //轮播成员数量
    public $time        = 3;        //轮播频次
    public $ex_len      = 3000;     //excel商品数量
    public $nodes       = [];
    public $cates       = [];
    public $son_nodes   = [];
    public $silent      = null;     // 静默

    /**
     * [cateGoods 同级子分类商品各n条]
     */
    public function cateGoods()
    {
        $sql = "SELECT id cid,name FROM ngw_category WHERE pid = {$this->dparam['cid']}";
        $soncate = M()->query($sql,'all');
        foreach ($soncate as $v) {
            $this->silent = 1;  //静默
            $this->dparam['cid'] = $v['cid'];
            $this->dparam['page_no'] = 1;
            $this->dparam['page_size'] = $this->dparam['size'];
            $this->goods[$v['cid']] = $this->showGoods();
            $this->cates[] = ['cid'=>$v['cid'],'name'=>$v['name']];
        }
        if(empty($this->goods)) info('暂无该分类商品',-1);

        info(['status'=>1,'msg'=>'操作成功!','data'=>$this->goods,'cate'=>$this->cates]);
    }



    //{"cid":"","page_no":"","page_size":"","system":"","user_id":"","type":"","stype":""}
    /**
     * [show 展示商品]
     */
    public function showGoods()
    {
        // R()->delLike('lm');die;
        $this->gtype = 1;
        if($this->dparam['stype'] == 1) $this->gtype = 3;
        $this->getNodes();
        $goods = R()->getListPage($this->cate,$this->dparam['page_no'],$this->dparam['page_size']);
        if(count($goods)>1){
            if(!$this->silent){
                info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>R()->size($this->cate)]);
            }else{
                return $goods;
            }

        }
        $sql = $this->getSQL();
        $goods = M()->query($sql,'all');
        if(!$this->silent && empty($goods)) info('暂无该分类商品',-1);
        $this->redisToGoods($this->cate,$goods);
        $goods = $this->page($goods);
        if(!$this->silent){
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>R()->size($this->cate)]);
        }else{
            return $goods;
        }
    }


    /**
     * [showGoods excel展示商品]
     */
    public function showGoodsEx()
    {
        // R()->delLike('ex');die;
        $this->gtype = 2;
        if($this->dparam['stype'] == 1) $this->gtype = 4;
        $this->getNodesEx();
        $goods = R()->getListPage($this->cate,$this->dparam['page_no'],$this->dparam['page_size']);
        if(count($goods)>1)
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>R()->size($this->cate)]);
        $sql = $this->getSQL();
        // echo $this->cate;die;
        $goods = M()->query($sql,'all');
        if(!$this->silent && empty($goods)) info('暂无该分类商品',-1);
        $this->redisToGoods($this->cate,$goods);
        $goods = $this->page($goods);
        info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>R()->size($this->cate)]);
    }


    /**
     * [redisToGoods redis取商品]
     */
    private function redisToGoods($key,$list)
    {
        R()->addListAll($key,$list);
    }


    /**
     * [getWhere 获取查询的条件]
     */
    public function getSQL()
    {
        // D($this->nodes);die;
        if($this->gtype==1){
            $sql= "SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_show = 1 AND a.source = 1 AND a.status =1 AND b.category_id IN (".implode(',',$this->nodes).") OR a.num_iid in (SELECT a.num_iid FROM ngw_goods_category_ref a JOIN ngw_goods_info b ON a.num_iid = b.num_iid WHERE b.is_show = 1 AND b.source = 1 AND b.status =1 AND a.category_id IN(".implode(',',$this->nodes).")) ORDER BY a.is_front DESC ,score DESC";
        }

        if($this->gtype==2){
            $sql= "SELECT a.*,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_show = 1 AND a.source = 0 AND a.status =1 AND b.category IN ('".implode("','",$this->nodes)."') ORDER BY a.is_front DESC ,score DESC";
        }

        if($this->gtype==3){
            $sql= "SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_show = 1 AND a.source = 1 AND a.status =1 AND b.category_id IN (".implode(',',$this->nodes).") AND b.price <= 19.9 OR a.num_iid in (SELECT a.num_iid FROM ngw_goods_category_ref a JOIN ngw_goods_info b ON a.num_iid = b.num_iid WHERE b.is_show = 1 AND b.source = 1 AND b.status =1 AND a.category_id IN(".implode(',',$this->nodes)."))  ORDER BY a.is_front DESC ,score DESC";
        }

        if($this->gtype==4){
            $sql= "SELECT a.*,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_show = 1 AND a.source = 0 AND a.status =1 AND b.price <= 19.9 AND b.category IN ('".implode("','",$this->nodes)."')  ORDER BY a.is_front DESC ,score DESC";
        }
        // echo $sql;die;
        return $sql;
    }


    /**
     * [getNodes 获取本节点和所有子节点]
     */
    public function getNodes()
    {
        $sql = "SELECT * FROM ngw_category WHERE id = {$this->dparam['cid']}";
        $cate = M()->query($sql);
        if($this->dparam['stype'] == 1){
            $this->cate = "lm99_{$cate['id']}_".$cate['name'];
        }else{
            $this->cate = "lm_{$cate['id']}_".$cate['name'];
        }
        $sql = "SELECT id,name FROM ngw_category WHERE `left` >= {$cate['left']} AND `right` <= {$cate['right']}";
        $cates = M()->query($sql,'all');
        foreach ($cates as $v) $temp[] = $v['id'];

        $this->nodes = $temp;

        //获得所有下级(只深一层)分类
        $sql = "SELECT id,name FROM ngw_category WHERE `left` >= {$cate['left']} AND `right` <= {$cate['right']} AND depth = {$cate['depth']}+1";
        $son_cates = M()->query($sql,'all');
        foreach ($son_cates as $k => $v) {
            $this->son_nodes[] = ['cid'=>$v['id'],'name'=>$v['name']];
        }
    }


    /**
     * [getNodesEx 获取本节点和所有子节点]
     */
    public function getNodesEx()
    {
        if($this->dparam['stype'] == 1){
            $this->cate = "ex99_".$this->dparam['cname'];
        }else{
            $this->cate = "ex_".$this->dparam['cname'];
        }
        if($this->dparam['cname'] == '全部'){
            $sql = "SELECT DISTINCT name  FROM  ngw_category WHERE taobao_category_name IS NOT NULL";
            $cates = M()->query($sql,'all');
            foreach ($cates as $v) $temp[] = $v['name'];
            $this->nodes = ['全部']+$temp;
            foreach ($cates as $k => $v)
                $this->son_nodes[] = ['cname'=>$v['name']];
        }else{
            $this->nodes[] = $this->dparam['cname'];
        }
        // D($this->cate);die;
    }


    /**
     * [category 首页商品分类]
     */
    public function category()
    {
        $cates = [
                    ['name'=>'全部','cid'=>'1'],
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
        D($info);die;
        info('请求成功',1,$info);
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
//----











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
     * 商品开关(用于优惠券过期)
     */
    public function goodsSwitch()
    {
        if(!empty($this->dparam['num_iid'])){
            //更新商品的状态
            M()->query("update ngw_goods_online set status = 2 where num_iid = {$this->dparam['num_iid']}");
            //取出商品对应的分类名
            $sql = "select category cname from ngw_goods_online where num_iid = {$this->dparam['num_iid']} ";
            $cates = M()->query($sql)+['全部'];
            //删除 redis 中包含该分类的商品分类
            foreach($cates as $v) R()->delFeild($v);

            info('请求成功',1,[]);
        }
    }


    public function searchGoods() {
        $parmas = $this->dparam;
        if(empty($parmas['page_no']) || empty($parmas['page_size']) || !isset($parmas['system']) || !isset($parmas['title']))
            info('缺少参数', -1);

        //记录用户搜索
        if(!empty($parmas['user_id']))
            (UserRecordController::getObj())->searchRecord($parmas['user_id'],$parmas['title'],$parmas['system']);
        $query = !empty($parmas['query']) ? : false;
        $type = !isset($parmas['type']) ? '0,1' : $parmas['type'];

       //优先展示自己的商品
       $sql[] = "SELECT num_iid,title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,url,reduce,volume,source,rating,FORMAT(rating/100*deal_price*".(parent::PERCENT).", 2) userPrice FROM ngw_goods_online WHERE status = 1 AND store_type IN({$type}) AND title like '%".formattedData($parmas['title'])."%' AND source in(0,1) AND item_url is not null";
       //根据商品价格进行筛选
       if(!empty($parmas['start_price']) && !empty($parmas['maxPrice'])) {
            $sql[] = 'AND deal_price BETWEEN '.$parmas['start_price']. ' AND '.$parmas['maxPrice'];
       }
       //点击查看更多--库里的商品
       $sql[] = 'GROUP BY num_iid LIMIT '.($query ? ($parmas['page_no'] - 1) * $parmas['page_size'].','.$parmas['page_size'] : 3);

       $self = M()->query(implode($sql, ' '), 'all');

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
     * [hotTab 热门搜索]
     */
    public function hotTab()
    {
        $sql = "SELECT DISTINCT search_content FROM ngw_search_log LIMIT 0,10";
        $info = M()->query($sql,'all');
        foreach ($info as $v) $tabs[]=$v['search_content'];
        info('ok',1,$tabs);
    }



    //{"user_id":"","page_no":"","page_size":""}
    /**
     * [history 用户浏览记录]
     */
    public function history()
    {
        if(R()->hashFeildExisit('history_'.$this->dparam['user_id'],'click')){
            $total = array_filter(R()->getHashSingle('history_'.$this->dparam['user_id'],'click'),function($v){
                if($v['type'] == 1) return $v;  //1表示用户没有删的历史记录
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