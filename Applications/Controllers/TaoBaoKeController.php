<?php
/**
 * 淘宝订单信息获取类
 */
class TaoBaoKeController extends AppController {
    // 订单id
    public $id = [];
    //存储商品混淆id
    public $aggregate = [];
    //配置文件数组
    public static $params = [];
    //批量添加付款成功的数据入库
    public $sql = '';

    //存储商品列表服务api查询到的数据
    public $taobaoList = array ( 0 => array ( 'cid' => 50011173, 'istk' => true, 'mall' => false, 'nick' => 'abao261771126', 'open_auction_iid' => 'AAFdEeI9AD4pHCthcgxFzj7x', 'open_id' => 528632859015, 'open_iid' => 'AAFdEeI9AD4pHCthcgxFzj7x', 'post_fee' => '0.00', 'price' => '9.90', 'price_wap' => '9.90', 'reserve_price' => '12.80', 'shop_name' => '阿宝日用品店', 'title' => '宝宝卡通驱蚊贴儿童天然植物婴儿孕妇36贴防蚊贴成人蚊不叮手环', 'tk_rate' => '3000', ), 1 => array ( 'cid' => 122766001, 'istk' => true, 'mall' => false, 'nick' => '广州齐好化妆品有限公司', 'open_auction_iid' => 'AAHtEeI9AD4pHCthcgm4X9Du', 'open_id' => 545081829272, 'open_iid' => 'AAHtEeI9AD4pHCthcgm4X9Du', 'post_fee' => '0.00', 'price' => '29.00', 'price_wap' => '29.00', 'reserve_price' => '35.00', 'shop_name' => '齐好母婴', 'title' => '齐好.宝宝金水120ml + 花露水150ml套装 婴儿宝宝驱蚊止痒喷雾水', 'tk_rate' => '1000', ), );
    public function run() {
        if(empty(self::$params))
            self::$params = include DIR_CORE.'baiChuanConfig.php';
        foreach(self::$params['order'] as $v) {
            //初始化每次循环产生的数据存储变量
            $this->unsetVariable();
            TaoBaoApiController::__setas($v['appkey'], $v['secret']);
            $this->type = isset($v['type']) ? $v['type'] : '';
            $this->message();
        }
        echo '处理完成';
    }

    // 订单信息
    public function message() {
        $this->unsetVariable();
        // $resp = TaoBaoApiController::tmcMessagesConsumeRequest();
        // if(empty($resp['messages']['tmc_message']))
            // return;
        // $a = $resp['messages']['tmc_message'];
        $order = (json_decode(file_get_contents('http://localhost/test/2.json'), true))['tmc_message'];
        //获取订单id 用作确认消息
        $this->id = array_column($order,'id');
        $i = 0;
        foreach($order as $k => $v) {
            $content = json_decode($v['content'], true);
            //映射对应的订单状态
            list($content['msg'], $content['status']) = $this->option($v['topic']);
            //获取产生这笔订单的appkey
            $content['app_key'] = $v['pub_app_key'];
            //获取全部付款成功的商品混淆id 以及退款成功 付款成功的订单号
            if(2 == $content['status']) {
                foreach($content['auction_infos'] as $_v) {
                    $this->aggregate[2][$i]['auctionId'] = $_v['auction_id'];
                    $this->aggregate[2][$i++]['orderId'] = $_v['detail_order_id'];
                }
            } else if(4 == $content['status']) {
                $this->aggregate[5][] = $content['tid'];
            }
            $data[] = $content;
        }
        //通过商品列表服务api拿到明文id 以及邮费
        // $this->taobaoList = TaoBaoApiController::taeItemsListRequest();
        //订单入库
        $this->addOrder($data);
        return;
    }
    public function addOrder($data) {
        foreach($data as $v) {
            //key进行替换
            $v = $this->replaceField($v);
            //如果此字段为空则表示收到的这笔订单是正在退款中或者退款成功的
            !empty($v['auction_infos']) or $v['auction_infos'][] = $v;
            //给即将入库的参数 默认设置为空
            $v['open_id']           = ' ';   //明文id
            $v['post_fee']          = ' ';   //邮费
            $v['detail_order_id']   = ' ';   //auction_infos 字段 list类型里的订单号
            $v['auction_amount']    = ' ';   //购买件数
            $v['auction_pict_url']  = ' ';   //商品主图
            $v['auction_title']     = ' ';   //商品标题
            $v['shop_title']        = ' ';   //店铺名称
            !empty($v['order_id'])          or $v['order_id']  = ' ';   //订单号
            !empty($v['refund_id'])         or $v['refund_id'] = ' ';   //订单号
            !empty($v['create_order_time']) or $v['create_order_time'] = ' ';   //下单时间

            if(!empty($v['auction_infos'])) {
                //auction_infos字段是数组形式[以防万一 循环处理]
                foreach($v['auction_infos'] as $_v) {
                    //把auction_infos字段里的值和外面的值组合在一起进行处理入库
                    $v = array_merge($v, $_v);
                    unset($v['auction_infos']);
                    //匹配从商品列表服务api查出来的混淆id 获取到该商品明文id
                    foreach($this->taobaoList as $taobaoList) {
                        if($taobaoList['open_iid'] == $v['auction_id']) {
                            //减过邮费之后的价钱
                            $v['paid_fee']   = abs($v['paid_fee']) - abs($taobaoList['post_fee']) < 0 ? 0 : abs($v['paid_fee']) - abs($taobaoList['post_fee']);
                            //获取明文id
                            $v['open_id'] = $taobaoList['open_id'];
                        }
                    }
                    //把所有订单数据拼接成一条sql语句入库
                    $this->sql .= "('{$v['buyer_id']}', '{$v['order_id']}','{$v['open_id']}', {$v['post_fee']}, {$v['paid_fee']}, {$v['status']}, '{$v['msg']}', {$v['auction_amount']}, '{$v['auction_id']}', '{$v['seller_nick']}', '{$v['shop_title']}', '{$v['auction_title']}', '{$v['order_id']}', '{$v['refund_id']}', '{$v['detail_order_id']}', '{$v['create_order_time']}', '{$v['auction_pict_url']}'),";
                }
            }
        }
        $this->sql = rtrim($this->sql, ',');
        // M()->query($this->sql);
        // D($this->sql);
        // //确认消息
        // $this->confirmationMessage($this->id);
        $purchaseRecord = isset($this->aggregate[2]) ? array_column($this->aggregate[2], 'orderId') : [];
        $backOrder      = isset($this->aggregate[5]) ? $this->aggregate[5] : [];
        $order_id = array_diff($purchaseRecord, $backOrder); //付款成功 订单id
        D($backOrder);
        exit;
        $res      = array_diff($backOrder, $purchaseRecord); //退款成功 订单id
        // //先处理 付款成功 再处理退款成功的订单
        // empty($order_id) or $this ->notice(2, $order_id);
        // empty($res)      or $this ->notice(5, $res);
    }
    /**
     * [confirmationMessage 确认消息]
     */
    public function confirmationMessage($goodsId) {
        $id = array_chunk($goodsId, 200);
        foreach($id as $v) {
            TaoBaoApiController::tmcMessagesConfirmRequest(implode($v, ','));
        }
    }
    /**
     * [notice 分发处理消息队列]
     */
    public function notice($status = '', $data = '') {
        $record  = new RecordController;
        switch($status) {
            case 2:
                $record  ->updateOrderInfo($data);
                (SuccShopIncomeController::getObj())->incomeHandle($data);
                break;
            case 5:
                $record  ->purchaseRecord($data, 5);
                $record  ->updateOrderBack($data);
                (FailShopIncomeController::getObj())->incomeHandle($data);
                break;
        }

    }
    //情况变量
    public function unsetVariable() {
        $this->id        = [];
        $this->aggregate = [];
        $this->sql       = 'INSERT IGNORE INTO gw_order_status( `buyer_id` , `order_id` , `open_id` , `post_fee` , `paid_fee` , `status` , `msg` , `auction_amount` , `auction_id` , `seller_nick` , `shop_title` , `auction_title` , `oid`, `refund_id`, `detail_order_id` , `create_order_time` , `auction_pict_url`) VALUES ';
    }
   /**
    * 依据消息名称 映射 对应的名称 与状态号
    */
   public function option($name)
   {
        static $data = [
            'taobao_tae_BaichuanTradeCreated'       => ['创建订单', 1],
            'taobao_tae_BaichuanTradePaidDone'      => ['付款成功', 2],
            'taobao_tae_BaichuanTradeSuccess'       => ['交易成功', 3],
            'taobao_tae_BaichuanTradeRefundCreated' => ['创建退款', 4],
            'taobao_tae_BaichuanTradeRefundSuccess' => ['退款成功', 5],
            'taobao_tae_BaichuanTradeClosed'        => ['交易关闭', 6]
        ];
        return isset($data[$name]) ? $data[$name] : [$name, 0];
   }
    /**
     * [replaceField 重复字段替换]
     */
    private function replaceField($arr)
    {
        $data = [
            'tid'           => 'order_id',    //key  tid替换成order_id
            'refund_fee'    => 'paid_fee',
        ];
        foreach($arr as $k => $v) {
            if(array_key_exists($k, $data)) {
                $arr[$data[$k]] = $v;
                unset($arr[$k]);
            }
        }
        return $arr;
    }



    /**
     *
     * [addOrderId 添加订单id]
     */
    public function addOrderId()
    {
        if(empty($this->dparam['uid']) || empty($this->dparam['order_id']) || empty($this->dparam['taobao_nick']))
            info('缺少参数', -1);
        if(is_array($this->dparam['order_id'])) {
            foreach($this->dparam['order_id'] as $k => $v) {
                $this->dparam['order_id']     = (string)$v;
                $this->dparam['created_date'] = date('Y-m-d');
                if(!M('order') ->where(['order_id' => ['=', $v]]) ->select('single'))
                    $add = M('order')->add($this->dparam);
            }
            !empty($add) ? info('添加成功',1) : info('添加失败',-1);
        } else {
            info('类型格式不对',-1);
        }
    }
    /**
     * 记录版本号
     */
    public function deviceVer() {
        $params = $this->dparam;
        $type = !empty($params['type']) ? 1 : 0;
        $data = '缺少参数';
        if(!empty($params['device']) && !empty($params['status'])) {
            switch ($params['status']) {
                //查库
                case 1:
                    $data = M('device')->where(['deviceVer' => ['=', $params['device']]])->field('type')->select('single');
                    $data = isset($data['type']) ? $data['type'] : info('库里可能还没存在',-1);
                    break;
                //修改
                case 2:
                    $data = M('device')->where(['deviceVer' => ['=',$params['device']]])->save(['type' => $type]);
                    break;
                //添加
                case 3:
                    $data = M('device')->add(['deviceVer' => $params['device'], 'type' => $type]);
                    break;
            }
        }
        info('',(int)$data);
    }
}