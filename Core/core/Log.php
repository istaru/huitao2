<?php
//日志类
class Log {
    public static $startTime = null;
    public static function startTime($time = '') {
        self::$startTime = $time ? $time : microtime(true);
    }
    public static function writeLog($data = []) {
        if(empty(C('log:writeLog')) || !is_writeable(DIR)) return;
        if(!is_dir(DIR_RUNTIME))
            mkdir(DIR_RUNTIME);
        if(!is_dir(DIR_RUNTIME_LOG))
            mkdir(DIR_RUNTIME_LOG);
        if(!is_dir(DIR_RUNTIME_LOG.date('Ym'))) {
            mkdir(DIR_RUNTIME_LOG.date('Ym'));
        }
        $content = [
            "time"       => date('Y-m-d H:i:s'),
            "spendTime"  => round(microtime(true) - self::$startTime, 3).'s',
            "userIp"     => $_SERVER['REMOTE_ADDR'],
            "url"        => self::getUrl(),
            // "statusCode" => $_SERVER['REDIRECT_STATUS'],
            'parameter'  => $_POST,
            'return'     => $data,
        ];
        $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents(DIR_RUNTIME_LOG.date('Ym').DS.date('d').'.json', $content, FILE_APPEND);
    }
    public static function getUrl() {
        return $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
}