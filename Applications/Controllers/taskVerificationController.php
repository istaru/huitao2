<?php
//任务验证类
class taskVerificationController {
    //商品分享任务
    public static function commoditySharingTask($uid) {
        return M('share_log')->where("uid = '{$uid}' AND share_type = 1")->select('single');
    }
    //淘宝授权任务
    public static function taobaoMandateTask($uid) {
        return M('taobao_log')->where("uid = '{$uid}'")->select('single');
    }
    //完成一次好友邀请任务
    public static function aFriendInvitationTask($uid) {
        return M('friend_log')->where("sfuid = '{$uid}'")->select('single');
    }
    //完成一次下单任务
    public static function oneSinglePurchaseTask($uid) {
        return M('shopping_log')->where("uid = '{$uid}'")->select('single');
    }
    //成功邀请一个好友任务
    public static function successInviteaFriendTask($uid) {
        return M('uid_log')->where("uid = '{$uid}' AND score_type = 2")->select('single');
    }
    //好友累计两次购买任务
    public static function friendsAccumulatedTwoSingleTask($uid) {
        $data = M()->query("SELECT count(b.uid) num FROM(SELECT score_source FROM ngw_uid_log WHERE score_type=2 AND uid = '{$uid}') a JOIN(SELECT uid,order_id FROM ngw_order) b ON b.uid = a.score_source");
        return $data ? $data['num'] > 1 ? 1 : 0 : 0;
    }
    //好友累计两次确认收货
    public static function friendConfirmTheDeliveryOfTwoTimesTask($uid) {
        $data = M()->query("SELECT count(c.id) num FROM(SELECT score_source FROM ngw_uid_log WHERE score_type = 2 AND uid = '{$uid}') a JOIN(SELECT uid , order_id FROM ngw_order) b ON b.uid = a.score_source JOIN(SELECT id , order_id FROM ngw_order_status WHERE status = 3) c ON c.order_id = b.order_id");
        return $data ? $data['num'] > 1 ? 1 : 0 : 0;
    }

}