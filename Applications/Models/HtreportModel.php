<?php
class HtreportModel {
    //缓存用户的uid
    private static $uid = [];
    public function __construct() {
        self::$uid = connectionArray(M('tracking')->where("uid IS NOT NULL")->select('all'), 'uid');
    }
    //用户的分享记录
    public function shareRecord($startTime, $endTime) {
        return M()->query("SELECT count(uid) share, report_date FROM ngw_share_log WHERE uid IN(".(self::$uid).") AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date", 'all');
    }
    //用户的分享率 (分享数/去重用户登录数)
    public function shareRate($startTime, $endTime) {
        return M()->query("SELECT round(a. share / b.num * 100 , 2) s_percent, a.report_date FROM( SELECT count(uid) share , report_date FROM ngw_share_log WHERE uid IN(".(self::$uid).") AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date) a JOIN(SELECT count(DISTINCT uid) num , report_date FROM ngw_uid_login_log WHERE uid IN(".(self::$uid).") AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date) b ON b.report_date = a.report_date", 'all');
    }
    //用户新增 (成功注册且有过淘宝授权的)
    public function channelsNewlyAdded($startTime, $endTime) {
        return M()->query("SELECT count(DISTINCT uid) track_num ,report_date FROM ngw_taobao_log WHERE uid IN(".(self::$uid).") AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date", 'all');
    }
    //邀请新增 (由渠道用户邀请过来的好友 成功注册且有过淘宝授权的)
    public function inviteNewlyAdded($startTime, $endTime) {
        return M()->query("SELECT count(a.objectId) invatation_num , report_date FROM( SELECT objectId FROM ngw_uid WHERE sfuid IN(".(self::$uid).") AND report_date BETWEEN '{$startTime}' AND '{$endTime}') a JOIN ngw_taobao_log b ON b.uid = a.objectId GROUP BY report_date", 'all');
    }
    //有效用户 (有过淘宝授权的有过点击记录的)
    public function validUser($startTime, $endTime) {
        return M()->query("SELECT report_date , count(DISTINCT uid) click_num FROM ngw_click_log WHERE uid IN(".(self::$uid).") AND uid IN(SELECT uid FROM ngw_taobao_log) AND report_date BETWEEN '{$startTime}' AND '{$endTime}') GROUP BY report_date", 'all');
    }
    //留存用户数 (除掉今天新增的用户 有过登录记录的 则为留存用户)
    public function leaveBehindUser() {

    }
    //统计用户的下单额(刨除掉退单的)
    public function userPurchaseNumber($startTime, $endTime) {
        return M()->query("SELECT sum(fee) , report_date FROM ngw_shopping_log WHERE uid IN(".(self::$uid).") AND order_id NOT IN(SELECT order_id FROM ngw_shopping_log WHERE order_status = 5) AND order_status = 2 AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date", 'all');
    }
    //由用户带来的利润 (刨除掉退单的)
    public function userProfits() {

    }
    //统计用户的好友的下单额(刨除掉退单的)
    public function userFriendPurchaseNumber($startTime, $endTime) {
        return M()->query("SELECT sum(fee) , report_date FROM ngw_shopping_log WHERE uid IN(select objectId from ngw_uid where sfuid in(".(self::$uid).")) AND order_id NOT IN(SELECT order_id FROM ngw_shopping_log WHERE order_status = 5) AND order_status = 2 AND report_date BETWEEN '{$startTime}' AND '{$endTime}' GROUP BY report_date", 'all');
    }
    //由用户的好友带来的利润 (刨除掉退单的)
    public function userFriendProfits() {

    }
}
