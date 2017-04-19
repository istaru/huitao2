<?php
class TaoBaoKeController {
    //存储商品列表服务api查询到的数据
    private $taobaoList = [];
    //淘宝api实例类
    public $taoBaoApi = null;
    //订单状态
    private static $option = [
        'taobao_tae_BaichuanTradeCreated'       => ['创建订单', 1],
        'taobao_tae_BaichuanTradePaidDone'      => ['付款成功', 2],
        'taobao_tae_BaichuanTradeSuccess'       => ['交易成功', 3],
        'taobao_tae_BaichuanTradeRefundCreated' => ['创建退款', 4],
        'taobao_tae_BaichuanTradeRefundSuccess' => ['退款成功', 5],
        'taobao_tae_BaichuanTradeClosed'        => ['交易关闭', 6]
    ];
    public function run() {
        foreach((include DIR_CORE.'baiChuanConfig.php')['order'] as $v) {
            $this->taoBaoApi = new TaoBaoApiController($v['appkey'], $v['secret']);
            $this->message();
        }
        echo '处理完成<br/>';
    }
    // 订单信息
    public function message() {
        $this->taoBaoApi = new TaoBaoApiController(23597987, '035bff81056833b5a95ee1145eae7620');
        //获取所有订单信息
        // $resp = $this->taoBaoApi->tmcMessagesConsumeRequest();
        // file_put_contents('order.txt', json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
        $resp = (json_decode(file_get_contents('order.json'), true));
        if(empty($resp['messages']['tmc_message'])) return;
            $order = $resp['messages']['tmc_message'];
        //存储付款成功以及退款成功的单号
        $paymentSuccess = $refundSuccess = [];
        foreach($order as $v) {
            $content = $v['content'];
            // $content = json_decode($v['content'], true);
            //映射对应的订单状态
            list($content['msg'], $content['status']) = isset(self::$option[$v['topic']]) ? self::$option[$v['topic']] : [$v['topic'], 0];
            //获取全部付款成功的商品混淆id 以及退款成功 付款成功的订单号
            if(2 == $content['status']) {
               foreach($content['auction_infos'] as $v) {
                    $auctionId[]        = $v['auction_id'];
                    $paymentSuccess[]   = $v['detail_order_id'];
               }
            } else if(5 == $content['status']) $refundSuccess[] = $content['tid'];
            $data[] = $content;
        }
        //批量进行请求api拿到明文id 以及邮费
        $this->taobaoList = $this->taoBaoApi->taeItemsListRequest([], array_unique($auctionId));
        //批量补全商品表中没有的商品
        $this->complementGoodsOnline(!empty($this->taobaoList) ? array_column($this->taobaoList, 'open_id') : []);
        //订单批量入库
        echo $this->addOrder($data);
        //批量确认消息
        $this->taoBaoApi->tmcMessagesConfirmRequest(array_column($order,'id'));
        //批量处理付款成功的订单id
        empty($paymentSuccess) OR $this->notice(2, array_diff($paymentSuccess, $refundSuccess));
        //批量处理退款成功的订单id
        empty($refundSuccess) OR $this->notice(5, array_diff($refundSuccess, $paymentSuccess));
        return;
    }
    //通过传入商品明文id 查询相关api数据入库
    public function complementGoodsOnline($numIid = []) {
        if(empty($numIid) && empty($_POST['num_iid'])) return;
        $numIid  = $numIid ? : explode(',', $_POST['num_iid']);
        $pendingTreatment = array_diff($numIid, array_column(M('goods_online')->where('num_iid in('.(connectionArray($numIid)).')')->field('num_iid')->select('all'), 'num_iid'));
        if(!$pendingTreatment) return;
        if(!$this->taoBaoApi) {
            foreach((include DIR_CORE.'baiChuanConfig.php')['order'] as $v) {
                $this->taoBaoApi  = new TaoBaoApiController($v['appkey'], $v['secret']);
                $this->taobaoList = $this->taoBaoApi->taeItemsListRequest($pendingTreatment);
                if($this->taobaoList) break;
            }
        }
        $sql = 'INSERT INTO ngw_goods_online('.('`'.implode('`,`', array_keys($this->setFileds([], 'goods_online'))).'`').') VALUES ';
        foreach($this->taoBaoApi->tbkItemInfoGetRequest($pendingTreatment) as $v) {
            foreach($this->taobaoList as $val) {
                if($v['num_iid'] == $val['open_id']) {
                    $v = array_merge($val, $v);
                    $v['small_images']  = json_encode($v['small_images'], JSON_UNESCAPED_UNICODE);  //小图列表
                    $v['store_type']    = $v['mall'] ? 0 : 1;   //平台类型
                    $v['rating']        = $v['tk_rate'] / 100;  //淘宝客佣金比率
                    $v['source']        = 10;
                    $v['status']        = 2;
                    $v['created_date']  = date('Y-m-d');
                    $sql .= '('.implode($this->setFileds($this->replaceField($v, [
                        'pic_url'       => 'pict_url',      //主图链接
                        'shop_name'     => 'store_name',    //店铺名称
                        'reserve_price' => 'price',         //商品一口价
                        'nick'          => 'seller_name'    //卖家旺旺
                    ]), 'goods_online'), ',').'),';
                }
            }
        }
        $this->exec($sql);
        echo 'ngw_online_goods表已处理完成...<br/>';
    }
    public function exec($sql) {
        return substr($sql, -1) == ',' ? M()->exec(rtrim($sql, ',')) : '';
    }
    private function addOrder($data) {
        if(!empty($data)) {
            $sql = 'INSERT IGNORE INTO ngw_order_status('.('`'.implode('`,`', array_keys($this->setFileds())).'`').') VALUES ';
            foreach($data as $v) {
                $v = $this->replaceField($v, [
                    'tid'        => 'order_id',
                    'refund_fee' => 'paid_fee'
                ]);
                !empty($v['auction_infos'])     OR $v['auction_infos'][] = $v;
                foreach($v['auction_infos'] as $_v) {
                    //把auction_infos字段里的值和外面的值组合在一起进行处理入库
                    $v = array_merge($v, $_v);
                    //匹配从商品列表服务api查出来的混淆id 获取到该商品明文id
                    foreach($this->taobaoList as $taobaoList) {
                        if($taobaoList['open_iid'] == $v['auction_id'] and 2 == $v['status']) {
                            //减过邮费之后的价钱
                            $v['paid_fee'] = abs($v['paid_fee']) - abs($taobaoList['post_fee']) < 0 ? 0 : abs($v['paid_fee']) - abs($taobaoList['post_fee']);
                            //获取明文id
                            $v['open_id']  = $taobaoList['open_id'];
                            //补全order表中明文id
                            M('order')->where(['order_id' => ['=', $v['order_id']]])->save(['num_iid' => $v['open_id']]);
                        }
                    }
                    //生成退单时间
                    !empty($v['create_order_time']) OR $v['create_order_time'] = date('Y-m-d H:i:s');
                    $sql .= '('.implode($this->setFileds($v), ',').'),';
                }
            }
            $this->exec($sql);
            return '订单添加完成...<br/>';
        }
    }
    //字段替换
    public function replaceField($data, $key) {
         foreach($data as $k => $v) {
            if(array_key_exists($k, $key)) {
                $data[$key[$k]] = $v;
                unset($data[$k]);
            }
         }
        return $data;
    }
    //捕捉到订单信息入库之后的后续动作
    private function notice($status = '', $data = '') {
        $record  = new RecordController;
        switch($status) {
            case 2:
                $record->updateOrderInfo($data);
                // (SuccShopIncomeController::getObj())->incomeHandle($data);
                break;
            case 5:
                $record->purchaseRecord($data, 5);
                $record->updateOrderBack($data);
                // (FailShopIncomeController::getObj())->incomeHandle($data);
                break;
        }
    }
    //设置表字段以及默认值
    public function setFileds($value = [], $table = 'order_status', $unsetFiled = ['id', 'updatedAt', 'createdAt']) {
        //缓存表字段
        static $tables = null;
        static $filed = null;
        if($table != $tables) {
            $tables = $table;
            $filed = M($tables)->getTableFields();
        }
        foreach($filed as $v)
            $fileds[$v] = isset($value[$v]) ? is_string($value[$v]) && !empty($value[$v]) ? "'{$value[$v]}'" : $value[$v] : 'NULL';
        foreach($unsetFiled as $v)
            unset($fileds[$v]);
        return $fileds;
    }
    //用户付款成功时存储订单号以及用户信息
    public function addOrderId() {
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
