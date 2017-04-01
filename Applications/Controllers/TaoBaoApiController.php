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
        $this->$goodsId = [];
        if($open_iids)
            $this->getOpenIids($open_iids);
        else if($num_iids)
            // $this->$goodsId = array_chunk($num_iids, 50);
        $res  = [];
        $data = [];
        foreach($this->$goodsId as $v) {
            $res[] = $this->goodsListRequest('', $v);
        }
        foreach($res as $v) {
            if(is_array($v)) {
                foreach($v as $_v) {
                    $data[] = $_v;
                }
            }
        }
        return $data;
    }
    public function goodsListRequest($num_iids, $open_iids = []) {
        $resp = $this->taoBao->send([
            'fields'    => 'title,nick,cid,price,post_fee,promoted_service,shop_name',
            'num_iids'  => $num_iids,
            'open_iids' => $open_iids,
            'method'    => 'taobao.tae.items.list',
        ]);
        return !empty($resp['tae_items_list_response']['items']['x_item']) ? $resp['tae_items_list_response']['items']['x_item'] : '';
    }
    public function getOpenIids($data) {
       $str = '';
       if(empty($data))
           return $str;
       foreach($data as $k => &$v) {
           if(300 <= mb_strlen($v) + mb_strlen($str)) break;
           $str .= $v.',';
           unset($data[$k]);
       }
       $this->$goodsId[] = rtrim($str, ',');
       $this->getOpenIids($data);
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
            // 'is_tmall'      => false,
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
}