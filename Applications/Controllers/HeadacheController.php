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
            //1=安卓  2=IOS
            if($data['system'] == 1)
                $did = $this->queryDidLog("imei = '{$data['imei']}'");
            else if($data['system'] == 2)
                $did = $this->queryDidLog("idfa = '{$data['idfa']}'");
            else $this->return_json(false,'参数错误');
            empty($did) or $this->return_json(false,'已经激活过了');
            //删除入库时间小于今天的但还未激活的数据 以防止之前接的任务和本次接的任务回调地址混乱
            $createdAt = date('Y-m-d').' 00:00:00';
            M()->query("DELETE FROM gw_tracking WHERE (idfa = '{$data['idfa']}' OR imei = '{$data['imei']}' AND uid IS NULL) AND source = '{$data['source']}' AND createdAt < '{$createdAt}'");
            M('tracking')->add($data);
            $this->return_json(true,'ok');
        }
        $this->return_json(false, '缺少参数');
    }
    private function queryDidLog($s) {
        return M('did_log')->where($s)->select();
    }
    //ios 点击上报接口
    public function click() {
        $data = $_REQUEST;
        if(!isset($data['ip'], $data['idfa'], $data['callback_url']))
            $this->return_json(false,'缺少参数');
        //查询该idfa是否已经排重过
        if(M('tracking')->where(['idfa' => ['=', $data['idfa']]])->select()) {
            M('tracking')->where(['idfa' => ['=', $data['idfa']]])->save(['callback_url' => urldecode($data['callback_url'])]);
            $this->return_json(true,'ok');
        }
        $this->return_json(false,'还未通过效验');
    }
    //检查库里是否有这条记录
    public function checkImeiOrIdfa($imei = '', $idfa = '') {
        $sql = "SELECT * FROM gw_tracking WHERE %s AND status = 1 limit 1";
        if(!empty($imei))
            return M()->query(sprintf($sql, "imei = '{$imei}'"));
        if(!empty($idfa))
            return M()->query(sprintf($sql, "idfa = '{$idfa}'"));
        return;
    }
    //接口关闭
    public function close($data) {
        $data['report_date'] = date('Y-m-d');
        $data['type']       = 1;
        $data['status']     = 5;
        M('tracking')->where("imei = '{$data['imei']}'")->save($data);
        return '产品已经下线';
    }
    // ios 安卓注册 回调
    public function registerActivation($data = []) {
        //检查库里是否有这条记录 并且获取到这条记录的来源 实现扣量~~
        $source = $this->checkImeiOrIdfa(!empty($data['imei']) ? $data['imei'] : '', !empty($data['idfa']) ? $data['idfa'] : '');
        if(empty($source))
            return;
        $up = [
            'status'      => 3,
            'type'        => $data['type'],
            'uid'         => $data['uid'],
            'report_date' => date('Y-m-d')
        ];
        if(!empty($data['imei'])) {
            // info($this->close($data),-1);
            M('tracking')->where("imei = '{$data['imei']}'")->save($up);
            $data = M()->query("SELECT idfa,callback_url FROM gw_tracking WHERE status = 3 AND source = '{$source['source']}' AND imei NOT IN( SELECT imei FROM gw_tracking WHERE status IN(2 , 4) AND imei IS NOT NULL)", 'all');
            if(count($data) >= $this->androidBase) {
                foreach($this->rand($data, $this->androidPercentage) as $v) {
                    M('tracking')->where(['imei' => ['=', $data[$v]['imei']]])->save(['status' => 2]);
                    get_curl(urldecode($data[$v]['callback_url']));
                    unset($data[$v]);
                }
                M('tracking')->where("imei in(".connectionArray($data, 'imei').")")->save(['status' => 4]);
            }
        } else if(!empty($data['idfa'])) {
            M('tracking')->where("idfa = '{$data['idfa']}'")->save($up);
            $data = M()->query("SELECT idfa,callback_url FROM gw_tracking WHERE status = 3 AND source = '{$source['source']}' AND idfa NOT IN( SELECT idfa FROM gw_tracking WHERE status IN(2 , 4) AND idfa IS NOT NULL)", 'all');
            if(count($data) >= $this->iosBase) {
                foreach($this->rand($data, count($data)) as $v) {
                    M('tracking')->where(['idfa' => ['=', $data[$v]['idfa']]])->save(['status' => 2]);
                    get_curl(str_replace('amp;','',urldecode($data[$v]['callback_url'])));
                    unset($data[$v]);
                }
                M('tracking')->where("idfa in(".connectionArray($data, 'idfa').")")->save(['status' => 4]);
            }
        }
    }
    //下单用户进行真实回调  不扣量
    public static function singleCallback($data = []) {
        if(!empty($data['imei'])) {
            //统一进行 回调
            $a = M()->query("SELECT callback_url FROM gw_tracking WHERE imei IN(".connectionArray($data['imei']).") AND status != 2",'all');
            if(!empty($a)) {
                foreach($a as $v)
                    get_curl($v['callback_url']);
                foreach($data['imei'] as $k => $v)
                    M('tracking')->where("imei = '{$v}'")->save(['status' => 2,'type' => 2, 'uid' => $k]);
            }
        }
        if(!empty($data['idfa'])) {
            //统一进行 回调
            $a = M()->query("SELECT callback_url FROM gw_tracking WHERE idfa IN(".connectionArray($data['idfa']).") AND status != 2",'all');
            if(!empty($a)) {
                foreach($a as $v)
                    get_curl($v['callback_url']);
                foreach($data['idfa'] as $k => $v)
                    M('tracking')->where("idfa = '{$v}'")->save(['status' => 2,'type' => 2, 'uid' => $k]);
            }
        }
    }
    public function rand($data, $rand) {
        return array_rand($data, $rand) ? array_rand($data, $rand) : [array_rand($data, $rand)];
    }
    public function return_json($status,$msg) {
        echo json_encode(['success' => $status, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
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