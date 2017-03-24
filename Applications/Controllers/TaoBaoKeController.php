<?php
/**
 * 淘宝订单信息获取类
 */
class TaoBaoKeController extends AppController {
    public static $taoBaoApi = '';
    public $type = ''; //手机系统 1 安卓 2ios
    public static $params = [];
    public function run() {
        if(empty(self::$params))
            self::$params = include DIR_CORE.'baiChuanConfig.php';
        foreach(self::$params['order'] as $v) {
            TaoBaoApiController::__setas($v['appkey'], $v['secret']);
            $this->type = isset($v['type']) ? $v['type'] : '';
            $this->message();
        }
        echo '处理完成';
    }
    // 订单信息
    public function message() {
        $resp = TaoBaoApiController::tmcMessagesConsumeRequest();
        if(empty($resp['messages']))
            return;
        $a = $resp['messages'];
        $setSMessageIds = array_column($a['tmc_message'],'id');
        $data = [];
        foreach($a as $k => $v) {
            foreach($v as $_k => $_v) {
                $content = json_decode($_v['content'], true);
                list($content['msg'], $content['status']) = $this->option($_v['topic']);
                $data[] = $content;
            }
        }
        //处理 入库
        $this ->addDatabaseContent($data, $setSMessageIds);
        return;
    }
    public function addDatabaseContent($data, $setSMessageIds) {
        $res = $order_id = [];
        foreach($data as $v) {
            //key进行替换
            $v = $this ->replaceField($v);
            //判断是所属退款消息 还是订单付款消息
            if(!empty($v['auction_infos'])) {
                if(!empty($v['auction_infos']['status']))
                    $v['auction_infos'][] = $v['auction_infos'];
                foreach($v['auction_infos'] as $_k => $_v) {
                    $_v = array_merge($v, $_v);
                    if($_v['status'] == 2) {
                        $order_id[] = $_v['detail_order_id'];
                        $n = TaoBaoApiController::taeItemsListRequest($_v['auction_id']);
                        if(!empty($n[0])) {
                            $n = $n[0];
                            $_v['post_fee'] = $n['post_fee'];   //邮费
                            $_v['price'] = abs($_v['paid_fee'])-abs($n['post_fee']); //减过邮费之后的价格
                            //如果是负数 则表示得到的邮费不准确保留真实价格
                            $_v['paid_fee'] = $_v['price'] < 0 ? 0.00 : $_v['price'];
                            $_v['open_id']  = $n['open_id'];    //商品明文id
                        }
                    }
                    unset($_v['auction_infos']);
                    $_v['type'] = $this->type;
                    $add = M('order_status')->add($_v);
                }
            } else {
                if($v['status'] == 5)
                    $res[] = $v['order_id'];
                $v['type'] = $this->type;
                $add = M('order_status')->add($v);
            }
        }
        //确认消息
        $this->confirmationMessage($setSMessageIds);
        $order_id = array_diff($order_id, $res); //付款成功 订单id
        $res      = array_diff($res, $order_id); //退款成功 订单id
        //先处理 付款成功 再处理退款成功的订单
        empty($order_id) or $this ->notice(2, $order_id);
        empty($res)      or $this ->notice(5, $res);
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
        $incomes = new IncomesController;
        switch($status) {
            case 2:
                $record  ->updateOrderInfo($data);
                $incomes ->buySuccess($data);
                break;
            case 5:
                $record  ->purchaseRecord($data, 5);
                $record  ->updateOrderBack($data);
                $incomes ->buyFail($data);
                break;
        }

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