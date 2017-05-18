<?php
class HeadacheController {
    //排重接口 兼容 ios和安卓
    public function tracking() {
        $data = $_REQUEST;
        if(!empty($data['source']) && !empty($data['system']) && !empty($data['imei']) || !empty($data['idfa'])) {
            //查看did_log表中是否存在这条记录 如果存在则表示已经激活过
            !empty($data['imei']) ? $did['imei'] = $data['imei'] : $did['idfa'] = $data['idfa'];
            if(userBehaviorVerificationController::queryUserDeviceInformation($did)) self::info('已经激活过了');
            //删除这个来源下已经存在库里并且还没有激活过的这条数据 报存最新的这条防止回调地址请求混乱
            M()->exec("DELETE FROM ngw_tracking WHERE (idfa = '{$did}' OR imei = '{$did}' AND status=1) AND source = '{$data['source']}'");
            M('tracking')->add($data);
            self::info('ok', 1);
        }
        self::info('缺少参数');
    }
    public static function info($msg,$status = 0) {
        info(['success' => $status, 'msg' => $msg]);
    }
    //ios 点击上报接口
    public function click() {
        $data = $_REQUEST;
        isset($data['ip'], $data['idfa'], $data['callback_url'], $data['source']) OR self::info('缺少参数');
        $where = "idfa = '{$data['idfa']}' and source = '{$data['source']}' and callback_url is null";
        M('tracking')->where($where)->select() ? M('tracking')->where($where)->save(['callback_url' => urldecode($data['callback_url'])]) : self::info('还未通过效验或重复点击');
        self::info('ok', 1);
    }
    //接口关闭
    public function close($data) {
        self::info('产品已经下线');
    }
    // ios 安卓注册 回调
    public function registerActivation($data = ['imei' => '123','uid' => '123', 'type' => 1]) {
        $where = !empty($data['imei']) ? "imei = '{$data['imei']}'" : "idfa = '{$data['idfa']}'";
        //检查库里是否有这条记录 并且获取到这条记录的来源 实现扣量~~
        if(!$self = M('tracking')->where($where)->select('single')) return;
        //暂存到库里 状态改为3
        M('tracking')->where($where)->save([
            'status'      => 3,
            'type'        => $data['type'],
            'uid'         => $data['uid'],
            'report_date' => date('Y-m-d')
        ]);
        //查询这个来源下还没回调的数据 并且这些数据其他来源也没有回调过
        $callback = M()->query("SELECT idfa , imei , status , callback_url,source FROM ngw_tracking WHERE( idfa NOT IN( SELECT idfa FROM ngw_tracking WHERE status = 2 AND idfa IS NOT NULL) OR imei NOT IN( SELECT imei FROM ngw_tracking WHERE status = 2 AND imei IS NOT NULL)) AND status = 3 AND source = '{$self['source']}'", 'all');
        //获取回调率以及回调基数
        list($base, $percentage) = $this->setBase($self['system']);
        if(count($callback) >= $base) {
            $key = array_rand($callback, $percentage);
            foreach(is_array($key) ? $key : [$key] as $v) {
                //数据状态改为2 回调对方时把对方返回值也入库处理
                $this->callback($callback[$v]['idfa'], $callback[$v]['imei'], $callback[$v]['callback_url'], 1);
                unset($callback[$v]);
            }
            $did = connectionArray($callback, 'did');
            M('tracking')->where("imei IN({$did}) OR idfa IN({$did})")->save(['status' => 4]);
        }
    }
    public function callback($idfa, $imei, $callbackUrl, $type) {
        M('tracking')->where("(imei = '{$imei}' OR idfa= '{$idfa}')")->save([
            'status'    => 2,
            'response'  => get_curl(str_replace('amp;','',urldecode($callbackUrl))),
            'type'      => $type
        ]);
    }
    //根据手机系统来设置回调率以及回调基数
    public function setBase($system) {
        static $file = [
            1 => [1, 1],
            2 => [1, 1]
        ];
        return isset($file[$system]) ? $file[$system] : [10000, 1];
    }
    //下单用户进行真实回调
    public function active($data) {
        if(!empty($data)) {
            $uid = connectionArray($data);
            $sql = "SELECT a.uid , a.idfa , a.imei , a.callback_url , a.type FROM( SELECT * FROM ngw_tracking WHERE status != 2) a JOIN( SELECT uid , idfa , imei FROM ngw_did_log WHERE uid IN({$uid}) GROUP BY imei , idfa) b ON a.idfa = b.idfa OR a.imei = b.imei";
            $data = M()->query($sql, 'all');
            //发起回调
            foreach($data as $v) {
                $this->callback($v['idfa'], $v['imei'],$v['callback_url'], 2);
            }
        }
    }

    public function query() {
        $edate=date("Y-m-d");
        $sdate=date("Y-m-d",strtotime($edate." -30 day"));
        if(isset($_REQUEST["sdate"])&&!empty($_REQUEST["sdate"])){
            $sdate = $_REQUEST["sdate"];
        }
        if(isset($_REQUEST["edate"])&&!empty($_REQUEST["edate"])){
            $edate = $_REQUEST["edate"];
        }
        $sql = "SELECT status , source , system , count(0) num FROM ngw_tracking WHERE status != 3 and createdAt between '".$sdate."' and '".$edate."'"." GROUP BY status , source , system ";
        $data = M()->query($sql,'all');
//        D($data);
        if($data){
            //字段映射
            $status = [
                1 => '未下载安装',
                2 => '已成功回调',
                4 => '已被扣量',
                5 => '接口关闭时产生的成功激活',
                6 => '回调失败'
            ];
            //1 是安卓 2是IOS
            $system = [ 1 => '安卓', 2 => 'IOS' ];
            foreach($data as &$v) {
                if (isset($system[$v['system']]))
                    $v['system'] = $system[$v['system']];
                if (isset($status[$v['status']]))
                    $v['status'] = $status[$v['status']];
            }
            //source-system->status->num的二维数组
            $exportdata=[];
            foreach ($data as &$v){
                $exportdata[$v['source']."-".$v['system']][$v['status']]=$v['num'];
            }
            // D($exportdata);
            //对数组按键排序输出，不然呈现时顺序会变
            ksort($exportdata);
            info("列出成功",1,$exportdata);
         // info("列出成功",'1',$data);
        }
      info("暂无数据",'-2');
    }
}