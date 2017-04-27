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
    // public $step        = 20;       //轮播成员数量
    // public $time        = 3;        //轮播频次
    public $ex_len      = 3000;     //excel商品数量
    public $nodes       = [];
    public $cates       = [];
    public $son_nodes   = [];
    public $silent      = null;     // 静默

    //{"cid":"1","size":"1","stype":"1"}
    /**
     * [cateGoods 同级子分类商品各n条]
     */
    public function cateGoods()
    {
        //取出该 cid 下的所有一级子分类 cid
        $sql = "SELECT id cid,name FROM ngw_category WHERE `type` = 0 AND pid = {$this->dparam['cid']}";
        $soncate = M()->query($sql,'all');
        foreach ($soncate as $v) {
            $this->silent = 1;  //静默
            $this->dparam['cid'] = $v['cid'];
            $this->dparam['page_no'] = 1;
            $this->dparam['page_size'] = $this->dparam['size'];
            $tempgoods = $this->showGoods();    //循环执行父分类下的所有一级分类的数据
            if (!empty($tempgoods)) {
                $this->goods[$v['cid']] = $tempgoods;
                $this->cates[] = ['cid'=>$v['cid'],'name'=>$v['name']];
            }
        }
        if(empty($this->goods)) info('暂无该分类商品',-1);

        info(['status'=>1,'msg'=>'操作成功!','data'=>$this->goods,'cate'=>$this->cates]);
    }


    //{"cname":"全部","size":"1","stype":"1"}
    /**
     * [cateGoods 同级子分类商品各n条]
     */
    public function cateGoodsEx()
    {
        //取出该 cid 下的所有一级子分类 cid
        if($this->dparam['cname'] != '全部')
            $sql = "SELECT DISTINCT name  FROM  ngw_category WHERE `type` = 2 AND name = '{$this->dparam['cname']}'";
        else
            $sql = "SELECT DISTINCT name  FROM  ngw_category WHERE `type` = 2";
        $soncate = M()->query($sql,'all');
        foreach ($soncate as $v) {
            $this->silent = 1;  //静默
            $this->dparam['cname'] = $v['name'];
            $this->dparam['page_no'] = 1;
            $this->dparam['page_size'] = $this->dparam['size'];
            $this->dparam['stype'] = 0;
            // echo $v['name'];
            $tempgoods = $this->showGoodsEx();    //循环执行父分类下的所有一级分类的数据
            if (!empty($tempgoods)) {
                $this->goods[$v['name']] = $tempgoods;
                $this->cates[] = $v['name'];
            }
        }
        if(empty($this->goods)) info('暂无该分类商品',-1);

        info(['status'=>1,'msg'=>'操作成功!','data'=>$this->goods,'cate'=>$this->cates]);
    }



    //{"cname":"品牌","size":"1"}
    /**
     * [cateGoods 同级子分类商品各n条(品牌)]
     */
    public function cateGoodsBoard()
    {
        //取出该品牌下的所有子品牌
        $sql = "SELECT name cname FROM ngw_category WHERE `type` = 1 AND pname = '{$this->dparam['cname']}'";
        $soncate = M()->query($sql,'all');
        foreach ($soncate as $v) {
            $this->silent = 1;  //静默
            $this->dparam['cname'] = $v['cname'];
            $this->dparam['page_no'] = 1;
            $this->dparam['page_size'] = $this->dparam['size'];
            $tempgoods = $this->showGoodsBoard();
            if (!empty($tempgoods)) {
                $this->goods[$v['cname']] = $tempgoods;
                $this->cates[] = $v['cname'];
            }
        }
        if(empty($this->goods)) info('暂无该分类商品',-1);

        info(['status'=>1,'msg'=>'操作成功!','data'=>$this->goods,'cate'=>$this->cates]);
    }

    //{"cid":"","page_no":"","page_size":"","system":"","user_id":"","stype":""}
    /**
     * [show 展示商品]
     */
    public function showGoods()
    {
        // R()->delLike('lm');die;
        $this->gtype = 1;
        if($this->dparam['stype'] == 1) $this->gtype = 3;
        $this->getNodes();
        $page_no = ($this->dparam['page_no'] - 1) * $this->dparam['page_size'] ;
        $page_size = $page_no + $this->dparam['page_size'] - 1;
        $goods = R()->getListPage($this->cate,$page_no,$page_size);
        if(R()->size($this->cate)>1){
            if(!$this->silent){
                info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>count($goods)]);
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
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>count($goods)]);
        }else{
            return $goods;
        }
    }


    //{"cname":"","page_no":"","page_size":"","system":"","user_id":"","stype":""}
    /**
     * [showGoods excel展示商品]
     */
    public function showGoodsEx()
    {
        // R()->delLike('ex');die;
        $this->gtype = 2;
        if($this->dparam['stype'] == 1) $this->gtype = 4;
        $this->getNodesEx();
        $page_no = ($this->dparam['page_no'] - 1) * $this->dparam['page_size'] ;
        $page_size = $page_no + $this->dparam['page_size'] - 1;
        $goods = R()->getListPage($this->cate,$page_no,$page_size);

        if(R()->size($this->cate)>1)
            if(!$this->silent){
                info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>count($goods)]);
            }else{
                return $goods;
            }
        $sql = $this->getSQL();
        // echo $this->cate;die;
        $goods = M()->query($sql,'all');
        if(!$this->silent && empty($goods)) info('暂无该分类商品',-1);
        $this->redisToGoods($this->cate,$goods);
        $goods = $this->page($goods);
        if(!$this->silent){
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'son_cate'=>$this->son_nodes,'total'=>count($goods)]);
        }else{
            return $goods;
        }
    }


    //{"cname":"","page_no":"","page_size":"","system":"","user_id":""}
    /**
     * [showGoodsBoard 品牌商品展示]
     */
    public function showGoodsBoard()
    {
        $this->gtype = 6;
        $this->getNodesBoard();
        $page_no = ($this->dparam['page_no'] - 1) * $this->dparam['page_size'] ;
        $page_size = $page_no + $this->dparam['page_size'] - 1;
        $goods = R()->getListPage($this->cate,$page_no,$page_size);
        if(R()->size($this->cate)>1){
            if(!$this->silent){
                info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);
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
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);
        }else{
            return $goods;
        }
    }


    /**
     * [soldGoods 热卖商品(混合)]
     */
     public function soldGoods()
     {
        $this->gtype = 5;
        $page_no = ($this->dparam['page_no'] - 1) * $this->dparam['page_size'] ;
        $page_size = $page_no + $this->dparam['page_size'] - 1;
        $goods = R()->getListPage('soldLists',$page_no,$page_size);
        if(R()->size('soldLists')>1)
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);
        $sql = $this->getSQL();
        $goods = M()->query($sql,'all');
        if(!$this->silent && empty($goods)) info('暂无该分类商品',-1);
        $this->redisToGoods('soldLists',$goods);
        // $goods = $this->page($goods);
        info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);

     }


     /**
      * [newGoods 新品(混合)]
      */
     public function newGoods()
     {
        $this->gtype = 7;
        $page_no = ($this->dparam['page_no'] - 1) * $this->dparam['page_size'] ;
        $page_size = $page_no + $this->dparam['page_size'] - 1;
        $goods = R()->getListPage('newLists',$page_no,$page_size);
        if(R()->size('newLists')>1)
            info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);
        $sql = $this->getSQL();
        $goods = M()->query($sql,'all');
        if(!$this->silent && empty($goods)) info('暂无该分类商品',-1);
        $this->redisToGoods('newLists',$goods);
        $goods = $this->page($goods);
        info(['status'=>1,'msg'=>'操作成功!','data'=>$goods,'total'=>count($goods)]);
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
        // 联盟商品
        if($this->gtype==1){
            $sql= "SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.source = 1 AND a.status =1 AND b.category_id IN (".implode(',',$this->nodes).") OR a.num_iid in (SELECT a.num_iid FROM ngw_goods_category_ref a JOIN ngw_goods_info b ON a.num_iid = b.num_iid WHERE b.is_show = 1 AND b.source = 1 AND b.status =1 AND a.category_id IN(".implode(',',$this->nodes).")) GROUP BY a.num_iid ORDER BY a.is_front DESC ,score DESC";
        }

        //excel优惠券商品
        if($this->gtype==2){
            $sql= "SELECT a.*,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.source = 0 AND a.status =1 AND b.category IN ('".implode("','",$this->nodes)."') GROUP BY a.num_iid ORDER BY a.is_front DESC ,score DESC";
        }

        //9.9联盟商品
        if($this->gtype==3){
            $sql= "SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.source = 1 AND a.status =1 AND b.category_id IN (".implode(',',$this->nodes).") AND b.price <= 19.9 OR a.num_iid in (SELECT a.num_iid FROM ngw_goods_category_ref a JOIN ngw_goods_info b ON a.num_iid = b.num_iid WHERE b.is_show = 1 AND b.source = 1 AND b.status =1 AND a.category_id IN(".implode(',',$this->nodes).")) GROUP BY a.num_iid ORDER BY a.is_front DESC ,score DESC";
        }
        //9.9 excel商品
        if($this->gtype==4){
            $sql= "SELECT a.*,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.source = 0 AND a.status =1 AND b.price <= 19.9 AND b.category IN ('".implode("','",$this->nodes)."') GROUP BY a.num_iid ORDER BY a.is_front DESC ,score DESC";
        }

        //热卖商品
        if($this->gtype==5){
            $sql="SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.is_sold = 1 AND a.status=1 GROUP BY a.num_iid ORDER BY a.is_front DESC,score DESC";
        }

        //品牌商品
        if($this->gtype==6){
            $sql="SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 1 AND a.is_show = 1 AND a.status = 1 AND b.category_id IN (".implode(',',$this->nodes).") GROUP BY a.num_iid ORDER BY a.is_front DESC,a.score DESC";
        }

        //新品
        if($this->gtype==7){
            $sql="SELECT a.*,FORMAT((b.rating/100*b.price*".parent::PERCENT."),2) as rating,b.title,b.seller_name nick,b.url,b.store_type,b.pict_url,b.price,b.category_id cid,b.category,b.deal_price zk_final_price,b.item_url,b.reduce,b.volume,concat('".parent::SHARE_URL."',b.num_iid) share_url FROM ngw_goods_info a JOIN ngw_goods_online b ON a.num_iid = b.num_iid AND a.favorite_id = b.favorite_id WHERE a.is_board = 0 AND a.is_show = 1 AND a.is_new = 1 AND a.status=1 GROUP BY a.num_iid ORDER BY a.is_front DESC,score DESC";
        }
        // echo $sql;die;
        return $sql;
    }


    /**
     * [getNodes 获取本节点和所有子节点]
     */
    public function getNodes()
    {
        $sql = "SELECT * FROM ngw_category WHERE `type` = 0 AND id = {$this->dparam['cid']}";
        $cate = M()->query($sql);
        if($this->dparam['stype'] == 1){
            $this->cate = "lm99_{$cate['id']}_".$cate['name'];
        }else{
            $this->cate = "lm_{$cate['id']}_".$cate['name'];
        }
        $sql = "SELECT id,name FROM ngw_category WHERE `type` = 0 AND `left` >= {$cate['left']} AND `right` <= {$cate['right']}";
        $cates = M()->query($sql,'all');
        foreach ($cates as $v) $temp[] = $v['id'];

        $this->nodes = $temp;

        //获得所有下级(只深一层)分类
        $sql = "SELECT id,name FROM ngw_category WHERE `type` = 0 AND  `left` >= {$cate['left']} AND `right` <= {$cate['right']} AND depth = {$cate['depth']}+1";
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
            $sql = "SELECT DISTINCT name  FROM  ngw_category WHERE `type` = 2";
            $cates = M()->query($sql,'all');
            foreach ($cates as $v) $temp[] = $v['name'];
            $this->nodes = ['全部']+$temp;
            foreach ($cates as $k => $v)
                $this->son_nodes[] = ['cname'=>$v['name']];
        }else{
            $temp[] = $this->dparam['cname'];
            $this->nodes = $temp;
        }
        // D($this->cate);die;
    }


    /**
     * [getNodesBoard 获取品牌 cid]
     */
    public function getNodesBoard()
    {
        $this->cate = "board_".$this->dparam['cname'];
        if($this->dparam['cname'] == '品牌'){
            $sql = "SELECT id,name  FROM  ngw_category WHERE `type` = 1";
        }else{
            $sql = "SELECT id,name  FROM  ngw_category WHERE `type` = 1 AND `name` = '{$this->dparam['cname']}'";
        }
        $cates = M()->query($sql,'all');
        $this->nodes = array_column($cates,'id');
    }


    /**
     * [category 首页商品分类]
     */
    public function category()
    {
        $cates = [
            ['name'=>'全部','cid'=>'1'],
            ['name'=>'女装','cid'=>'113','icon_url'=>RES_SITE.'resource/img/category/img_sort_01.png','content'=>'T恤、衬衫、连衣裙'],
            ['name'=>'鞋包','cid'=>'134','icon_url'=>RES_SITE.'resource/img/category/img_sort_02.png','content'=>'凉鞋、拖鞋、单鞋'],
            ['name'=>'美妆个护','cid'=>'145','icon_url'=>RES_SITE.'resource/img/category/img_sort_03.png','content'=>'保养、护肤'],
            ['name'=>'内衣','cid'=>'154','icon_url'=>RES_SITE.'resource/img/category/img_sort_04.png','content'=>'文胸、保暖内衣'],
            ['name'=>'男装','cid'=>'240','icon_url'=>RES_SITE.'resource/img/category/img_sort_05.png','content'=>'外套、休闲裤、衬衫'],
            ['name'=>'衣饰配件','cid'=>'161','icon_url'=>RES_SITE.'resource/img/category/img_sort_06.png','content'=>'裤装、卫衣'],
            ['name'=>'母婴亲子','cid'=>'166','icon_url'=>RES_SITE.'resource/img/category/img_sort_07.png','content'=>'婴儿车、奶瓶'],
            ['name'=>'家电','cid'=>'172','icon_url'=>RES_SITE.'resource/img/category/img_sort_08.png','content'=>'家电、厨房电器'],
            ['name'=>'数码','cid'=>'178','icon_url'=>RES_SITE.'resource/img/category/img_sort_09.png','content'=>'手机、平板电脑'],
            ['name'=>'运动','cid'=>'198','icon_url'=>RES_SITE.'resource/img/category/img_sort_010.png','content'=>'健身、户外'],
            ['name'=>'游戏动漫','cid'=>'203','icon_url'=>RES_SITE.'resource/img/category/img_sort_011.png','content'=>'桌游、手办'],
            ['name'=>'美食','cid'=>'210','icon_url'=>RES_SITE.'resource/img/category/img_sort_012.png','content'=>'休闲零食、茶水饮料'],
            ['name'=>'日常家具','cid'=>'221','icon_url'=>RES_SITE.'resource/img/category/img_sort_013.png','content'=>'床上用品、卧室家具'],
            ['name'=>'办公学习','cid'=>'230','icon_url'=>RES_SITE.'resource/img/category/img_sort_014.png','content'=>'办公用品、文具'],
        ];
        info('ok',1,$cates);
    }


    /**
     * [exCategory Excel分类]
     */
    public function categoryEx()
    {
        $sql = "SELECT DISTINCT name cname FROM ngw_category WHERE `type` = 0 AND taobao_cid IS NOT NULL ";
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


    /**
     * [range 以时间戳0-9的规律取余获得当前的index位置,index 用户分割商品列表再重新拼接]
     */
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


    //{"user_id":"Nuwd8XEsBs","num_iid":"525103323591","type":"1"}
    /**
     * [share 分享记录用户分享行为]
     */
    public function share()
    {
        if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'] || empty($this->dparam['type']))) info('参数不全',-1);

        (UserRecordController::getObj()) -> shareRecord($this->dparam['user_id'],$this->dparam['num_iid'],$this->dparam['type'],1); //1分享商品
        // (UserRecordController::getObj()) -> shareRecord($this->dparam['user_id'],0,3,0); //1分享商品
        info('ok',1);
    }


    //{"user_id":"Nuwd8XEsBs","num_iid":"525103323591","type":"1"}
    /**
     * [detail 点击商品详情时记录商品点击行为]
     */
    public function detail()
    {
        if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'] || empty($this->dparam['type']))) info('参数不全',-1);

        //记录用户点击及浏览历史记录
        (UserRecordController::getObj()) -> clickRecord($this->dparam['user_id'],$this->dparam['num_iid'],$this->dparam['type']);


        if(!R()->hashFeildExisit('detailLists',$this->dparam['num_iid'])){

            $sql                = " SELECT * FROM ngw_goods_online WHERE num_iid = '{$this->dparam['num_iid']}' ";
            $info               = M()->query($sql,'single');
            empty($info) && info('商品不存在',-1);
            $info['share_url']  = parent::SHARE_URL.$this->dparam['num_iid'];
            R()->hsetnx('detailLists',$this->dparam['num_iid'],$info,$this->expire);

        }

        $info = R()->getHashSingle('detailLists',(string)$this->dparam['num_iid']);
        info('请求成功',1,$info);
    }

    /**
     * [sortGoods 按分数排序按前置和分数降序排列]
     */
    // private function sortGoods($arr)
    // {
    //     foreach ($arr as $k => $v) {
    //         $sort[$k]   = $v['score'];
    //         $front[$k]  = $v['is_front'];
    //     }
    //     array_multisort($front,SORT_DESC,$sort,SORT_DESC,$arr);
    //     return $arr;
    // }
//----











    /**
     * 获取邀请页的三个商品详情
     */
    public function getApplyGoods(){
        $sql = "SELECT pict_url,price,reduce,price-reduce  as sell_price from ngw_goods_online where status ='1' order by reduce/price desc limit 30";
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
        $title = formattedData($parmas['title']);
        //记录用户搜索
        if(!empty($parmas['user_id']))
            (UserRecordController::getObj())->searchRecord($parmas['user_id'],$parmas['title'],$parmas['system']);
        $query = !empty($parmas['query']) ? : false;
        $type = !isset($parmas['type']) ? '0,1' : $parmas['type'];
        //点击查看更多 --针对库里的商品 默认显示三条
        $limit = 'GROUP BY num_iid LIMIT '.($query ? ($parmas['page_no'] - 1) * $parmas['page_size'].','.$parmas['page_size'] : 3);
        //根据商品价格进行筛选
        $priceScreening = !empty($parmas['start_price']) && !empty($parmas['maxPrice']) ? ' AND deal_price BETWEEN '.$parmas['start_price']. ' AND '.$parmas['maxPrice'] : ' ';
        //优先展示score评分高的商品
       $sql = "SELECT a.num_iid , a.title , a.seller_name nick , a.pict_url , a.price , a.deal_price zk_final_price , a.item_url , a.url , a.reduce , a.volume , a.source , a.rating ,
            FORMAT( a.rating / 100 * a.deal_price * ".parent::PERCENT." , 2) userPrice , b.score FROM(
                SELECT * FROM ngw_goods_online WHERE status = 1 AND store_type IN({$type}) AND title LIKE '%{$title}%' AND source IN(0 , 1) {$priceScreening} AND item_url IS NOT NULL {$limit}
            ) a LEFT JOIN ngw_goods_info b ON b.num_iid = a.num_iid ORDER BY score DESC";

       $self = M()->query($sql, 'all');
        //当query 为false 或 库里展示商品小于要查询的商品数量时 查询淘宝客商品
        if(!$query || count($self) < $parmas['page_size'])
            $data = (new TaoBaoApiController('23630111', 'd2a2eded0c22d6f69f8aae033f42cdce'))->tbkItemGetRequest($parmas);

        if(!empty($data['taobaoGoods'])) {
            $numIid = [];
            foreach($data['taobaoGoods'] as &$v) {
                //获取numIid
                foreach(explode('&', (parse_url($v['item_url']))['query']) as $val) {
                    $item = explode('=', $val);
                    $numIid[$item[0]] = $item[1];
                }
                $v['num_iid'] = isset($numIid['id']) ? $numIid['id'] : '';
                //生成商品分享链接
                $v['share_url'] = null;
                //字段映射区分淘宝集市和天猫商品
                $v['store_type'] = $v['user_type'] ? 0 : 1;
                unset($v['user_type']);
            }
        }
        foreach($self as &$v) $v['share_url'] = parent::SHARE_URL . $v['num_iid'];
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
        // $sql = "SELECT DISTINCT search_content FROM ngw_search_log LIMIT 0,10";
        // $info = M()->query($sql,'all');
        // foreach ($info as $v) $tabs[]=$v['search_content'];
        $tabs = ['t恤','家具用品','双肩包','益智玩具','盆栽','健身器','沙滩裙'];
        info('ok',1,$tabs);
    }



    //{"user_id":"8NoO8sqDbo","page_no":"","page_size":""}
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
        $data=M()->query("select num_iid,title,pict_url,price,reduce,store_name,volume from ngw_goods_online where num_iid='".I('num_iid')."'");
        info('请求成功',1,$data);
    }

    /**
     * [delRedisCateGoods description]
     * @param  [type] $type [1.lm联盟商品 2.ex商品]
     */
    public function delRedisCateGoods($type)
    {
        $keylike = $type == 1 ? 'lm' : ($type == 2 ? 'ex' : '' );
        R()->delFeild('detailLists');
        R()->delFeild('soldLists');
        R()->delLike('board');

        if(empty($keylike)) return;
        R()->delLike($keylike);
    }

}