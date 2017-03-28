<?php
/**
 * 淘宝订单信息获取类
 */
class TaoBaoKeController extends AppController {
    public $sql = '';
    // 商品id
    public $id = [];
    //存储商品付款成功单号 以及商品退单单号
    public $aggregate = [];
    //配置文件数组
    public static $params = [];
    //存储商品列表服务api查询到的数据
    public $taobaoList = [];
    //设置类属性默认值
    public function setVariable() {
        $this->aggregate = [
            2 => [],    //存储付款成功单号
            5 => [],    //存储退款成功单号
        ];
        $this->sql       = 'INSERT IGNORE INTO gw_order_status('.('`'.implode('`,`', array_keys($this->setFileds())).'`').') VALUES ';
    }
    public function run() {
        if(empty(self::$params) && empty(self::$params = (include DIR_CORE.'baiChuanConfig.php')['order']))
            info('缺少appkey');
        foreach(self::$params as $v) {
            //初始化每次循环产生的数据
            $this->setVariable();
            TaoBaoApiController::__setas($v['appkey'], $v['secret']);
            $this->message();
        }
        echo '处理完成';
    }

    // 订单信息
    public function message() {
        //获取所有订单信息
        // $resp = TaoBaoApiController::tmcMessagesConsumeRequest();
        // if(empty($resp['messages']['tmc_message']) || !$order = $resp['messages']['tmc_message']) return;
        $order = (json_decode(file_get_contents('http://localhost/test/2.json'), true))['tmc_message'];
        //获取订单id 用作确认消息
        $this->id = array_column($order,'id');
        foreach($order as $k => $v) {
            $content = json_decode($v['content'], true);
            //映射对应的订单状态
            list($content['msg'], $content['status']) = $this->option($v['topic']);
            //获取产生这笔订单的appkey
            $content['app_key'] = $v['pub_app_key'];
            //获取全部付款成功的商品混淆id 以及退款成功 付款成功的订单号
            if(2 == $content['status']) {
                foreach($content['auction_infos'] as $_k => $_v) {
                    $auctionId[] = $_v['auction_id'];
                    $this->aggregate[2][] = $_v['detail_order_id'];
                }
            } else if(5 == $content['status']) {
                $this->aggregate[5][] = $content['tid'];
            }
            $data[] = $content;
        }
        //通过商品列表服务api拿到明文id 以及邮费
        // empty($auctionId) or $this->taobaoList = TaoBaoApiController::taeItemsListRequest('', $auctionId));
        //订单入库
        $this->addOrder($data);
    }
    public function addOrder($data) {
        foreach($data as $v) {
            //key进行替换
            $v = $this->replaceField($v);
            //如果此字段为空则表示收到的这笔订单是正在退款中或者退款成功的 也组合在一起循环处理
            !empty($v['auction_infos']) OR $v['auction_infos'][] = $v;
            foreach($v['auction_infos'] as $_v) {
                //把auction_infos字段里的值和外面的值组合在一起进行处理入库
                $v = array_merge($v, $_v);
                //匹配从商品列表服务api查出来的混淆id 获取到该商品明文id
                foreach($this->taobaoList as $taobaoList) {
                    //对于付款成功的订单需要减去邮费入库
                    if($taobaoList['open_iid'] == $v['auction_id'] and 2 == $v['status']) {
                        //减过邮费之后的价钱
                        $v['paid_fee']   = abs($v['paid_fee']) - abs($taobaoList['post_fee']) < 0 ? 0 : abs($v['paid_fee']) - abs($taobaoList['post_fee']);
                        //获取明文id
                        $v['open_id'] = $taobaoList['open_id'];
                    }
                }
                //把所有订单数据拼接成一条sql语句
                $this->sql .= '('.implode($this->setFileds($v), ',').'),';
            }
        }
        M()->query(rtrim($this->sql, ','));
        //确认消息
        TaoBaoApiController::tmcMessagesConfirmRequest($this->id);
        //处理付款成功的订单id
        empty($this->aggregate[2]) OR $this->notice(2, array_diff($this->aggregate[2], $this->aggregate[5]));
        //处理退款成功的订单id
        empty($this->aggregate[5]) OR $this->notice(5, array_diff($this->aggregate[5], $this->aggregate[2]));
    }

    /**
     * [notice 分发处理]
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
    //设置表字段以及默认值
    public function setFileds($value = []) {
        $filed = [
            'buyer_id'          => !isset($value['buyer_id'])            ? ' '    : $value['buyer_id'],
            'order_id'          => !isset($value['order_id'])            ? ' '    : $value['order_id'],
            'open_id'           => !isset($value['open_id'])             ? ' '    : $value['open_id'],
            'post_fee'          => !isset($value['post_fee'])            ?  0     : $value['post_fee'],
            'paid_fee'          => !isset($value['paid_fee'])            ?  0     : $value['paid_fee'],
            'status'            => !isset($value['status'])              ? 'NULL' : $value['status'],
            'msg'               => !isset($value['msg'])                 ? ' '    : $value['msg'],
            'auction_amount'    => !isset($value['auction_amount'])      ? 'NULL' : $value['auction_amount'],
            'auction_id'        => !isset($value['auction_id'])          ? ' '    : $value['auction_id'],
            'seller_nick'       => !isset($value['seller_nick'])         ? ' '    : $value['seller_nick'],
            'shop_title'        => !isset($value['shop_title'])          ? ' '    : $value['shop_title'],
            'auction_title'     => !isset($value['auction_title'])       ? ' '    : $value['auction_title'],
            'oid'               => !isset($value['oid'])                 ? ' '    : $value['oid'],
            'refund_id'         => !isset($value['refund_id'])           ? ' '    : $value['refund_id'],
            'detail_order_id'   => !isset($value['detail_order_id'])     ? ' '    : $value['detail_order_id'],
            'create_order_time' => !isset($value['create_order_time'])   ? ' '    : $value['create_order_time'],
            'auction_pict_url'  => !isset($value['auction_pict_url'])    ? ' '    : $value['auction_pict_url'],
        ];
        foreach($filed as &$v) {
            if(is_string($v) && !empty($v))
                $v = "'{$v}'";
        }
        return $filed;
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
     * [replaceField 字段替换]
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