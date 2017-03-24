<?php
class TaoBaoApiController {
    //初始化参数
    public static function __setas($appkey = '', $secret = '') {
        TaoBaoController::__setas($appkey, $secret);
    }
     //商品列表服务 https://open.taobao.com/doc2/apiDetail.htm?spm=a219a.7629140.0.0.iKkJiU&apiId=23731
    public static function taeItemsListRequest($open_iids) {
        $resp = TaoBaoController::send([
            'fields'    => 'title,nick,cid,price,post_fee,promoted_service,shop_name',
            'open_iids' => $open_iids,
            'method'    => 'taobao.tae.items.list',
        ]);
        return !empty($resp['tae_items_list_response']['items']['x_item']) ? $resp['tae_items_list_response']['items']['x_item'] : '';
    }
    //获取订单状态 http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.pJy3zR&apiId=21986
    public static function tmcMessagesConsumeRequest() {
        $resp = TaoBaoController::send([
            'quantity' => 200,
            'method'   => 'taobao.tmc.messages.consume',
        ]);
        return !empty($resp['tmc_messages_consume_response']) ? $resp['tmc_messages_consume_response'] : '';
    }
    //确认消息 http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.V2dlzx&apiId=21985
    public static function tmcMessagesConfirmRequest($id) {
        $id = array_chunk($id, 200);
        foreach($id as $v) {
            TaoBaoController::send([
                'method'        => 'taobao.tmc.messages.confirm ',
                's_message_ids' => implode($v, ',')
            ]);
        }
    }
    //淘宝客商品查询搜索 http://open.taobao.com/docs/api.htm?spm=a219a.7629065.0.0.1m81nR&apiId=24515
    public static function tbkItemGetRequest($paramster) {
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
        $taobaoGoods = TaoBaoController::send($data);
        $res['sum']         = !empty($taobaoGoods['tbk_item_get_response']['total_results']) ? $taobaoGoods['tbk_item_get_response']['total_results'] : 0;
        $res['taobaoGoods'] = !empty($taobaoGoods['tbk_item_get_response']['results']['n_tbk_item']) ? $taobaoGoods['tbk_item_get_response']['results']['n_tbk_item'] : [];
        return $res;
    }
}