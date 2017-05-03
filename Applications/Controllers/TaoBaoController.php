<?php
class TaoBaoController {
    public static $url    = 'http://gw.api.taobao.com/router/rest';
    public  $appKey       = '';
    public  $secret       = '';
    protected static $parameter = [
        'format'        => 'json',
        'v'             => '2.0',
        'sign_method'   => 'md5',
    ];
    public function __construct($appKey, $secret) {
        $this->appKey = $appKey;
        $this->secret = $secret;
    }
    public function send($data = [], $secret = '') {
        //获取appkey
        $data['app_key']   = isset($data['app_key']) ? $data['app_key'] : $this->appKey;
        //获取secret
        $secret            = !empty($secret) ? $secret : $this->secret;
        //组合公共参数以及业务参数
        $data              = array_merge(self::$parameter, $data);
        //获取加密签名
        $data['sign']      = self::sign($data,$secret);
        //获取时间戳
        $data['timestamp'] = self::$parameter['timestamp'];
        //发起请求
        $res               = get_curl(self::$url, $data);
        return self::returnArray($res);
    }
    protected static function sign($data, $secret) {
        $data['timestamp'] = self::$parameter['timestamp'] = date('Y-m-d H:i:s');
        ksort($data);
        $str = '';
        foreach($data as $k => $v)
            $str .= $k.$v;
        return strtoupper(md5($secret.$str.$secret));
    }
    public static function returnArray($data) {
        if(self::$parameter['format'] == 'json')
            return json_decode($data, true);
    }
}
