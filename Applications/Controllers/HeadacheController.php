<?php
class HeadacheController {
    //安卓扣量基数
    public $androidBase = 10;
    // 安卓回调数百分比
    public $androidPercentage = 2;
    //IOS 扣量基数
    public $iosBase = 1;
    //IOS 回调数百分比
    public $iosPercentage = 1;
    //排重接口 兼容 ios和安卓
    public function tracking() {
        $data = $_REQUEST;
        if(!empty($data['system']) && !empty($data['source']) && !empty($data['imei']) || !empty($data['idfa'])) {
            //查看did_log表中是否存在这条记录 如果存在则表示已经激活过
            if(M('did_log')->where(!empty($data['imei']) ? "imei = '{$data['imei']}'" : empty($data['idfa']) ?  : "idfa = '{$data['idfa']}'")->select())
                self::info('已经激活过了');
            //删除这个来源下已经存在库里并且还没有激活过的这条数据 报存最新的这条防止回调地址请求混乱
            M()->exec("DELETE FROM gw_tracking WHERE (idfa = '{$data['idfa']}' OR imei = '{$data['imei']}' AND status=1) AND source = '{$data['source']}'");
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
        if(!isset($data['ip'], $data['idfa'], $data['callback_url'], $data['source']))
            self::info('缺少参数');
        $where = [
            'idfa'  => $data['idfa'],
            'surce' => $data['source'],
            ['and']
        ];
        //查看这条数据是否已经通过排重
        if(M('tracking')->where($where)->select()) {
            M('tracking')->where($where)->save(['callback_url' => urldecode($data['callback_url'])]);
            self::info('ok', 1);
        }
        self::info('还未通过效验');
    }
    //接口关闭
    public function close($data) {
        $data['report_date'] = date('Y-m-d');
        $data['type']       = 1;
        $data['status']     = 5;
        M('tracking')->where("imei = '{$data['imei']}'")->save($data);
        self::info('产品已经下面');
    }
    // ios 安卓注册 回调
    public function registerActivation($data = ['imei' => '867992020283529', 'type' => 0, 'uid' => 123]) {
        //检查库里是否有这条记录 并且获取到这条记录的来源 实现扣量~~
        if(!$self = M('tracking')->where("imei = '{$data['imei']}' OR idfa = '{$data['idfa']}'")->select('single')) return;
        //暂存到库里 状态改为3
        M('tracking')->where(!empty($data['imei']) ? "imei = '{$data['imei']}'" : "idfa = '{$data['idfa']}'")->save([
            'status'      => 3,
            'type'        => $data['type'],
            'uid'         => $data['uid'],
            'report_date' => date('Y-m-d')
        ]);
        //查询这个来源下还没回调的数据 并且这些数据其他来源也没有回调过
        $callback = M()->query("SELECT idfa , imei , status , callback_url,source FROM gw_tracking WHERE( idfa NOT IN( SELECT idfa FROM gw_tracking WHERE status = 2 AND idfa IS NOT NULL) OR imei NOT IN( SELECT imei FROM gw_tracking WHERE status = 2 AND imei IS NOT NULL)) AND status = 3 AND source = '{$self['source']}'", 'all');
        foreach($callback as $k => $v) {
            $aggregate[$k]['callback'] = $v['callback_url'];
            $aggregate[$k]['did']      = empty($v['idfa']) ? $v['imei'] : $v['idfa'];
        }
        //判断手机系统决定采用那个基数来实现扣量
        if(count($callback) >= ($self['system'] == 2 ? $this->iosBase : $this->androidBase)) {
            foreach(array_rand($callback, $self['system'] == 2 ? count($callback) : $this->androidPercentage) as $v) {
                //数据状态改为2 回调对方时把对方返回值也入库处理
                M('tracking')->where("(imei = '{$callback[$v]['did']}' OR idfa= '{$callback[$v]['did']}') AND source = '{$callback[$v]['source']}'")->save([
                    'status'    => 2,
                    'response'  => get_curl(str_replace('amp;','',urldecode($callback[$v]['callback_url'])))
                ]);
                unset($callback[$v]);
            }
            $did = connectionArray($callback, 'did');
            M('tracking')->where("imei IN({$did}) OR idfa IN({$did})")->save(['status' => 4]);
        }
    }
    //下单用户进行真实回调
    public function active() {

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
        $sql = "SELECT status , source , system , count(0) num FROM gw_tracking WHERE status != 3 and createdAt between '".$sdate."' and '".$edate."'"." GROUP BY status , source , system ";
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