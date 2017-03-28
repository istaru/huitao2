<?php
class GoodsExcelController {
   public function query() {
        $params = $_REQUEST;
        $goodsInfo = $this->goodsSold($params['type'], $_REQUEST);
        if($params['type'] == 2)
            $this->chart($goodsInfo);
        $order = [];
        $backrate = [];
        foreach($goodsInfo as $v) {
            //获取付款成功的订单
            if($v['order_status'] == 2 && !empty($v['num_iid']))
                    $order[] = $v;
            //获取退款的订单
            else if($v['order_status'] == 5)
                $backrate[] = $v;
        }
        $order or info('暂无售出商品');
        //处理有过退单的订单信息
        foreach($order as $k => &$v) {
            $v['tpurchase']      = 0;
            $v['conversionrate'] = 0;
            //转换率 下单数/点击数
            $v['conversionrate'] = round($v['click'] ? $v['purchase']/$v['click']*100 : 0, 2);
            foreach($backrate as $value) {
                if(in_array($v['order_id'], $value)) {
                    $v['tpurchase'] = $value['purchase'];
                    //商品销售额 = 减去退单的真实销售额
                    $v['fee'] = $v['fee']+(-$value['fee']);
                    //所获利润 = 减去退单的所获真实利润
                    $v['benifit'] = $v['benifit']+(-$value['benifit']);
                }
            }
        }
        //总商品数
        $statistics['count']       = count($order);
        //获取所有商品总点击
        $statistics['click']       = array_sum(array_column($order, 'click'));
        //获取所有商品总销售额(下单并且不退单的金额)
        $statistics['fee']         = array_sum(array_column($order, 'fee'));
        //所获总利润 = 下单并且不退单的金额利润
        $statistics['grossProfit'] = array_sum(array_column($order, 'benifit'));
        //商品总下单数
        $statistics['purchase']    = array_sum(array_column($order, 'purchase'));
        //商品总退单数
        $statistics['tpurchase']   = array_sum(array_column($order, 'tpurchase'));
        //总退单率 = 商品总退单数/商品总下单数 $statistics['tpurchase']/$statistics['purchase']*100
        $statistics['backRate']    = round($statistics['purchase'] ? $statistics['tpurchase']/$statistics['purchase']*100 : 0, 2).'%';
        //总转换率=总下单数/总点击
        $statistics['actRate']     = round($statistics['purchase'] ? $statistics['purchase']/$statistics['click']*100 : 0, 2).'%';
        //因为除数是0 会报错 所以这里做下判断
        if($statistics['purchase']-$statistics['tpurchase']) {
            //平均成交价格 = 总销售额 / （总下单数 - 退单数
            $statistics['avgPrice'] = round($statistics['fee']/($statistics['purchase']-$statistics['tpurchase']),2);
            //平均成交利润 = 总利润 / （总下单数 - 退单数）
            $statistics['avgprofit'] = round(array_sum(array_column($order, 'benifit'))/($statistics['purchase']-$statistics['tpurchase']),2);
        } else {
            $statistics['avgPrice']  = 0;
            $statistics['avgprofit'] = 0;
        }
        //二维数组排序
        $data = arraySort($order, rtrim($params['order_para'], '1'), $params['order']);
        $data = [
            'total_data' => $statistics,
            'list'       => array_splice($data, ($params['page_no']-1)*$params['page_size'], $params['page_size'])
        ];
        info('OK', 1, $data);
   }
   public function chart($order) {
        $data = [];
        foreach($order as $v) {
            if(!empty($v['gw_name'])) {
                if($v['order_status'] == 5) {
                    $data[$v['gw_name']]['tfee']      = $v['fee'];
                    $data[$v['gw_name']]['tpurchase'] = $v['purchase'];
                    $data[$v['gw_name']]['tbenifit']  = $v['benifit'];
                } else {
                    $data[$v['gw_name']] = array_merge(isset($data[$v['gw_name']]) ? $data[$v['gw_name']] : [], $v);
                    $data[$v['gw_name']]['tfee']      = 0;
                    $data[$v['gw_name']]['tpurchase'] = 0;
                    $data[$v['gw_name']]['tbenifit']  = 0;
                }
                $data[$v['gw_name']]['cbenifit']    = $data[$v['gw_name']]['benifit']-$data[$v['gw_name']]['tbenifit'];
                $data[$v['gw_name']]['cfee']        = $data[$v['gw_name']]['fee']-$data[$v['gw_name']]['tfee'];
            }
        }
        //真实订单数量 = 订单数-退单数
        $sum = array_sum(array_column($data, 'purchase')) - array_sum(array_column($data, 'tpurchase'));
        $data = [
            'list'       => array_values($data),
            'total_data' => $sum
        ];
        info('ok', 1, $data);
   }

   public function goodsSold($type = 1, $params = []) {
        // 如果为2 则表示是查看图表形式
        $field = $type == 2 ? ' SUM(fee) fee , SUM(benifit) benifit , a.order_status , COUNT(a.num_iid) purchase , sum(b.click) click , gw_name ' : ' * ';
        return M()->query("SELECT {$field} FROM
        (
            SELECT SUM(fee) fee , SUM(benifit) benifit , order_status , order_id , num_iid , COUNT(num_iid) purchase , report_date FROM gw_shopping_log WHERE
                ( report_date BETWEEN '{$params['start_time']}' AND '{$params['end_time']}' AND order_status = 2 )
            OR
                ( report_date >= '{$params['start_time']}' AND order_status = 5 AND order_id IN (
                        SELECT order_id FROM gw_shopping_log WHERE report_date BETWEEN '{$params['start_time']}' AND '{$params['end_time']}' AND order_status = 2
                    )
                ) GROUP BY num_iid , order_status
        ) a LEFT JOIN( SELECT sum(click) click , num_iid FROM gw_goods_daily_report GROUP BY num_iid) b ON b.num_iid = a.num_iid AND a.order_status = 2
            LEFT JOIN( SELECT num_iid , gw_name , title FROM gw_goods_online GROUP BY num_iid) c ON c.num_iid = a.num_iid ".($type ==2 ? 'GROUP BY gw_name ,order_status' : ''), 'all');
   }

}

