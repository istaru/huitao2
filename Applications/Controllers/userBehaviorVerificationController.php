<?php
//任务验证以及好友绑定规则验证类
class userBehaviorVerificationController {
    //商品分享任务
    public static function commoditySharingTask($uid) {
        return M('share_log')->where("uid = '{$uid}' AND share_type = 1")->select('single');
    }
    //验证用户是否淘宝授权过
    public static function verifyUserTaobaoLicense($uid) {
        return M('taobao_log')->where("uid = '{$uid}'")->select('all');
    }
    //完成一次好友邀请任务
    public static function aFriendInvitationTask($uid) {
        return M('friend_log')->where("sfuid = '{$uid}'")->select('single');
    }
    //完成一次下单任务
    public static function oneSinglePurchaseTask($uid) {
        return M('shopping_log')->where("uid = '{$uid}'")->select('single');
    }
    //成功邀请一名好友任务
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
    //好友获得一个红包任务
    public static function friendsGetARedEnvelopeTask($uid) {
        return M('uid_bill_log')->where(['type' => ['=', 1], 'uid' => ['=', $uid]])->select('single');
    }
    //验证用户是否注册
    public static function userRegistration($uid) {
        return M('uid')->where(['objectId' => ['=', $uid]])->select('single');
    }
    //返回绑定某个淘宝授权id的所有uid
    public static function queryTaoBaoAuthId($taobaoId) {
        return M('taobao_log')->where(['taobao_id' => ['=', $taobaoId]])->select('all');
    }
    //查询用户的所有设备信息
    public static function queryUserDeviceInformation($data = []) {
        if(!$data = array_filter($data)) return;
        $s = '';
        foreach($data as $k => $v) {
            $s .= $k.'='."'{$v}'".' AND ';
        }
        return M('did_log')->where(rtrim($s, ' AND '))->order('id desc')->select('all');
    }
    //取出用户正在进行中的任务
    public static function userOngoingTask($uid) {
        return M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id NOT IN( SELECT task_id FROM ngw_task_log WHERE uid = '{$uid}') AND type = 1 ORDER by id ASC LIMIT 1", 'single');
    }
    //取出用户已完成的任务
    public static function userCompletedTask($uid) {
        $result = M()->query("SELECT id task_id,name,introduce,step,price,task_img FROM ngw_task WHERE id IN( SELECT task_id FROM ngw_task_log WHERE uid = '{$uid}') AND type = 1", 'all');
        return $result;
    }
}
