<?php
class TaoBaoApiController {
    //商品混淆id
    public $goodsId = '';
    public $taoBao = '';
    public function __construct($appKey, $secret) {
        $this->taoBao = new TaoBaoController($appKey, $secret);
    }
  /**
   * [taeItemsListRequest 商品列表服务 https://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7629140.0.0.iKkJiU&apiId=23731]
   * @param  array  $num_iids  [商品明文id 最多50个 优先级低于open_iids]
   * @param  array  $open_iids [混淆id最大长度为300]
   * @return [type]            [description]
   */
    public function taeItemsListRequest($num_iids = [], $open_iids = []) {
        $this->goodsId = $result = [];
        $data = $num_iids ? array_chunk($num_iids, 50) : $this->arrayLengthSegmentation($open_iids, 70);
        foreach($data as $v) {
            $resp = $this->taoBao->send([
                'fields'    => 'title,nick,pic_url,location,cid,price,post_fee,promoted_service,ju,shop_name',
                'num_iids'  => $num_iids ? implode(',', $v) : '',
                'open_iids' => $open_iids ? $v : '',
                'method'    => 'taobao.tae.items.list',
            ]);
            $result = array_merge(!empty($resp['tae_items_list_response']['items']['x_item']) ? $resp['tae_items_list_response']['items']['x_item'] : [], $result);
        }
        return $result;
    }
    //按照数组的值长度进行分割
    public function arrayLengthSegmentation($data, $length) {
        $str = '';
        foreach($data as $k => $v) {
            if($length > mb_strlen($v) + mb_strlen($str)) {
                $str .= $v.',';
                unset($data[$k]);
            }
        }
        $this->goodsId[] = rtrim($str, ',');
        !$data or $this->arrayLengthSegmentation($data, $length);
        return $this->goodsId;
    }
    //获取订单状态 http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.pJy3zR&apiId=21986
    public function tmcMessagesConsumeRequest() {
        $resp = $this->taoBao->send([
            'quantity' => 200,
            'method'   => 'taobao.tmc.messages.consume',
        ]);
        return !empty($resp['tmc_messages_consume_response']) ? $resp['tmc_messages_consume_response'] : '';
    }
    //确认消息 http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.V2dlzx&apiId=21985
    public function tmcMessagesConfirmRequest($id) {
        $id = array_chunk($id, 200);
        foreach($id as $v) {
            $this->taoBao->send([
                'method'        => 'taobao.tmc.messages.confirm ',
                's_message_ids' => implode($v, ',')
            ]);
        }
    }
    //淘宝客商品查询搜索 http://open.taobao.com/docs/api.htm?spm=a219a.7629065.0.0.1m81nR&apiId=24515
    public function tbkItemGetRequest($paramster) {
        $data = [
            'q'             => addslashes(htmlspecialchars(isset($paramster['title']) ? $paramster['title'] : '.')),
            'fields'        => 'num_iid,title,pict_url,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick',
            'sort'          => 'total_sales_des',
            'is_tmall'      => isset($paramster['type']) ? $paramster['type'] ? 'true' : 'false' : 'true',
            'method'        => 'taobao.tbk.item.get',
            'page_no'       => isset($paramster['page_no'])   ? $paramster['page_no']   : 1,
            'page_size'     => isset($paramster['page_size']) ? $paramster['page_size'] : 20,
        ];
        //以商品价格进行筛选
        empty($paramster['start_price']) or $data['start_price'] = $paramster['start_price'];
        empty($paramster['end_price'])   or $data['end_price']   = $paramster['start_price'];
        $taobaoGoods = $this->taoBao->send($data);
        $res['sum']         = !empty($taobaoGoods['tbk_item_get_response']['total_results']) ? $taobaoGoods['tbk_item_get_response']['total_results'] : 0;
        $res['taobaoGoods'] = !empty($taobaoGoods['tbk_item_get_response']['results']['n_tbk_item']) ? $taobaoGoods['tbk_item_get_response']['results']['n_tbk_item'] : [];
        return $res;
    }




    public function ibkUatmFavorites(){

          //self::__setas('23550152',"d27bdb2a9dba59cc20d7099f371d03d3");

          $rsp  = $this->taoBao->send([
            'fields'    => "favorites_title,favorites_id,type",
            'method'    => 'taobao.tbk.uatm.favorites.get',
            'page_no'       => 1,
            'page_size'     => 200

         ]);
       
         if(isset($rsp["tbk_uatm_favorites_get_response"]))return $rsp["tbk_uatm_favorites_get_response"];

    }

    public function tbkUatmFavoritesItem($param=null){
  

       // self::__setas('23550152',"d27bdb2a9dba59cc20d7099f371d03d3");

        $rsp = $this->taoBao->send([
            'fields'    => isset($param['fields'])  ? $param['fields'] : "num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type",
            'method'    => 'taobao.tbk.uatm.favorites.item.get',
            'page_no'       => isset($param['page_no'])  ? $param['page_no']   : 1,
            'page_size'     => isset($param['page_size'])  ? $param['page_size']   : 1,
            'favorites_id'=>isset($param['favorites_id'])  ? $param['favorites_id']   : 3519044,
            'adzone_id'=>isset($param['adzone_id'])  ? $param['adzone_id']   : 67202476,
            'platform'=>isset($param['platform'])  ? $param['platform']   : 1,
            
         ]);

         if(isset($rsp["tbk_uatm_favorites_item_get_response"]))return $rsp["tbk_uatm_favorites_item_get_response"];
    }




   



    /**
     * [tbkItemInfoGetRequest 淘宝客商品详情(简版) https://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.5FvwhC&apiId=24518]
     * @param  [type]  $openId   [商品明文id 最多40个 例如:123,456,789]
     * @param  integer $platform [链接形式：1：PC，2：无线，默认：2]
     * @return [type]            [description]
     */
    public function tbkItemInfoGetRequest($openId, $platform = 2) {
        $id = array_chunk($openId, 1);
        $resp = $res = [];
        foreach($id as $v) {
            $resp[] = $this->taoBao->send([
                'fields'    => 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume',
                'platform'  => $platform,
                'num_iids'  => implode($v, ','),
                'method'    => 'taobao.tbk.item.info.get',
                'app_key'   => 23630111
            ],'d2a2eded0c22d6f69f8aae033f42cdce');
        }
        foreach($resp as $v) {
            $res = array_merge(!empty($v['tbk_item_info_get_response']['results']['n_tbk_item']) ? $v['tbk_item_info_get_response']['results']['n_tbk_item'] : [], $res);
        }
        return $res;
    }

}