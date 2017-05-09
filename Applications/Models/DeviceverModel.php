<?php
class DeviceverModel {
    public static $table = 'device';
    //查询
    public static function query($status, $device) {
        return M(self::$table)->where("deviceVer = '{$device}' and status = {$status}")->field('deviceVer, type, webUrl, id')->select('single');
    }
    //修改
    public static function up($device, $status, $type, $url) {
        return M(self::$table)->where("deviceVer = '{$device}' and status = {$status}")->save(['type' => $type, 'webUrl' => $url]);
    }
    //添加
    public static function add($device, $type, $url, $status = 0) {
        return M(self::$table)->add([
            'deviceVer' => $device,
            'type'      => $type,
            'webUrl'    => $url,
            'status'    => $status,
        ]);
    }
}