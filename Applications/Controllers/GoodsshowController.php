<?php
/**
 * 商品展示
 */
class GoodsShowController extends AppController
{
    function __construct()
    {
        $this->status = 2;
        parent::__construct();
    }
    //{"cur_page":"1","page_size":10,"type":"","query":"男人的"}
    /**
     * [goods 商品列表]
     */
    public function goods()
    {
        file_put_contents(DIR.'/llogs.txt', json_encode($this->dparam));
        $cur_page = ($this->dparam['cur_page'] - 1) * $this->dparam['page_size'] ;
        $page_size = $cur_page + $this->dparam['page_size'] - 1;

        if(!empty($this->dparam['query']))
        {

            $cur_page = ($this->dparam['cur_page'] - 1) * $this->dparam['page_size'] ;
            $page_size = $this->dparam['page_size'];
            $where = " o.title like '%{$this->dparam['query']}%' ";
            if(!empty($this->dparam['type']) && $this->dparam['type'] == '9.9') $where .= ' and o.deal_price < 10 ';

            $list = A('Goods:getPageGoodsListSearch',[$cur_page,$page_size,$where]);

        }else{

            $cur_page = ($this->dparam['cur_page'] - 1) * $this->dparam['page_size'] ;
            $page_size = $cur_page + $this->dparam['page_size'] - 1;

            if(empty($this->dparam['type']))
            {

                $list = A('Goods:getPageGoodsList',[$cur_page,$page_size]);

            }else{
                if($this->dparam['type'] == 'today')
                {

                    $uid = !empty($this->dparam['user_id']) ? $this->dparam['user_id'] : '';
                    $list = A('Goods:getPageGoodsListForToday',[$cur_page,$this->dparam['page_size'],$uid]);

                }else if($this->dparam['type'] == '9.9'){
                    $where = ' o.deal_price < 20 ';
                    $type = $this->dparam['type'];
                    $list = A('Goods:getPageGoodsListForType',[$cur_page,$this->dparam['page_size'],$type,$where,false]);
                }else if(!empty($this->dparam['type'])){
                    $where = " o.gw_pid = {$this->dparam['type']} ";
                    $list = A('Goods:getPageGoodsListForType',[$cur_page,$page_size,$this->dparam['type'],$where,false]);
                }
            }

        }
        // echo count($list['list']);
        info(['msg'=>'请求成功','status'=>1,'total'=>$list['total'],'data'=>$list['list']]);

        // D($list);die;
        // info('请求成功',1,$list);
    }



    /**
     * [types_goods 商品分类]
     */
    public function getTypes()
    {
        $list = A('Goods:getTypes');
        foreach ($list as $k => &$v) {
            // if($v['id'] == 11) continue;
            $v['desc'] = L('goodsType')[$v['id']][1];
            $v['icon_url'] = RES_SITE."shoppingResource/goodstype/sort0{$v['id']}.png";
        }
        unset($list[10]);
        empty($list) && info('数据有误',-1);
        info('请求成功',1,$list);
    }

    //{"user_id":"Nuwd8XEsBs","num_iid":"525103323591","app":""}
    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'])) info('参数不全',-1);
        $detial = A('Goods:getGoodsDetail',[$this->dparam['num_iid']]);
        $detial['list']['share_url'] =  parent::SHARE_URL;
        $record = new RecordController;
        $record->clickRecord($this->dparam['user_id'],$this->dparam['num_iid']);
        info('请求成功',1,$detial['list']);
    }

    /**
     * 获取分享的商品的详情
     */
    public function getShareDetail()
    {
        $data=A('Goods:getShareDetail',[I('num_iid')]);
        info('请求成功',1,$data);
    }
    /**
     * 获取邀请页的三个商品详情
     */
    public function getApplyGoods(){
        $sql = "SELECT pict_url,price,reduce,price-reduce  as sell_price from gw_goods_online where top='1' and status ='1' order by reduce/price desc limit 30";
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
            M()->query("update gw_goods_online set status = 2 where num_iid = {$this->dparam['num_iid']}");
            A('Goods:delAllGoods');
            info('请求成功',1,[]);
        }
    }
    public function searchGoods() {
        $page_no   = isset($this->dparam['page_no'])   ? $this->dparam['page_no']   : 1;
        $page_size = isset($this->dparam['page_size']) ? $this->dparam['page_size'] : 20;
        $title     = formattedData(isset($this->dparam['title']) ? $this->dparam['title'] : '');
        $type      = !isset($this->dparam['type']) ? '0,1' : $this->dparam['type'];
       //优先展示自己的商品  
        $self = M()->query("SELECT num_iid,title,seller_name nick,pict_url,price,deal_price zk_final_price,item_url,reduce,volume FROM gw_goods_online WHERE status =1 AND store_type IN('{$type}') AND title like '%{$title}%' LIMIT ". (($page_no - 1) * $page_size).','.$page_size, 'all');
        //淘宝客商品查询
        TaoBaoApiController::__setas('23630111', 'd2a2eded0c22d6f69f8aae033f42cdce');
        $data = TaoBaoApiController::tbkItemGetRequest($this->dparam);
        info([
            'status' => 1,
            'msg'    => 'ok',
            'data'   => [
                'self'           => $self,
                'taobaoGoods'    => isset($data['taobaoGoods']) ? $data['taobaoGoods'] : [],
                'taobaoGoodsSum' => isset($data['sum'])         ? $data['sum']         : 0,
            ]
        ]);
    }

}