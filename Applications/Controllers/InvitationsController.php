<?php
class InvitationsController extends AppController
{
    /**
     * [invitations 邀请好友列表以及好友带来的收入]
     */
    public function invitations() {
        !empty($this->dparam['user_id']) or info('缺少用户id');
        $sql = "SELECT uid.head_img,uid.nickname,b.score_source,b.score_info msg,b.createdAt date_time FROM (select * from ngw_uid_log where uid = '{$this->dparam['user_id']}' AND score_type = 2) b JOIN ngw_uid uid ON uid.objectId=b.score_source";
        $sql1 = "SELECT uid.head_img,uid.nickname,b.score_source,b.score_info msg,b.createdAt date_time FROM (select * from ngw_income_log where uid='{$this->dparam['user_id']}') b JOIN ngw_uid uid ON uid.objectId=b.score_source";
        $data = array_merge(M()->query($sql, 'all'), M()->query($sql1, 'all'));
        if(!empty($data)) {
            $dataList = [];
            foreach($data as $v) {
                $temp = [
                    'msg'       => $v['msg'],
                    'date_time' => $v['date_time'],
                ];
                if(!isset($dataList[$v['score_source']]))
                    $dataList[$v['score_source']] = $v;
                $dataList[$v['score_source']]['data'][]   = $temp;
            }
            $invitations = [
                'msg'       => '请求成功',
                'status'    => 1,
                'data'      => array_reverse(array_values($dataList))
            ];
            info($invitations);
        }
        info('赶快去邀请好友吧!!', 1);
    }
    //排行榜
    public function rankingList() {
        $params = $_POST;
        //默认查询收益榜
        $type = empty($params['type']) ? 1 : $params['type'];
        //查看用户自己的收入(包含预估收入)和邀请人数
        $self = empty($params['user_id']) ? ['money' => 0, 'person_num' => 0] : M()->query("SELECT a.money,b.person_num FROM( SELECT sum(price) money , uid FROM ngw_uid_log WHERE status IN(1 , 2) AND uid = '{$params['user_id']}') a LEFT JOIN( SELECT count(0) person_num , uid FROM ngw_uid_log WHERE score_type = 2 AND uid = '{$params['user_id']}') b ON b.uid = a.uid");
       //月榜
       if(isset($params['query']) && $params['query'] == 'month') {
            $startTime = date('Y-m-d 00:00:00', strtotime(date('Y-m')));
            $endTime   = date('Y-m-d 24:00:00', strtotime(date('Y-m-t')));
        //周榜
       } else if(isset($params['query']) && $params['query'] == 'week') {
            $startTime = date('Y-m-d 00:00:00', strtotime('+'. 1 - date('w') .' days' ));
            $endTime   = date('Y-m-d 24:00:00', strtotime('+'. 7 - date('w') .' days' ));
        } else $startTime = $endTime = '';
        $str = '';
        if($startTime && $endTime)
            $str = "AND createdAt >= '{$startTime}' AND  createdAt < '{$endTime}'";
        //type 1 = 收入榜, 2 = 邀请榜
        if($type == 1) {
            $list = M()->query("SELECT b.nickname , b.head_img, a.friends_num FROM( SELECT sum(price) friends_num , uid FROM ngw_uid_log WHERE status = 2 {$str} GROUP BY uid ORDER BY friends_num DESC LIMIT 10) a LEFT JOIN( SELECT nickname , head_img , objectId FROM ngw_uid) b ON b.objectId = a.uid", 'all');
        } else if($type == 2) {
            $list = M()->query("SELECT b.nickname , b.head_img, a.friends_num FROM( SELECT count(0) friends_num , uid FROM ngw_uid_log WHERE score_type = 2 {$str} GROUP BY uid ORDER BY friends_num DESC LIMIT 10) a LEFT JOIN( SELECT nickname , head_img , objectId FROM ngw_uid) b ON b.objectId = a.uid", 'all');
        } else info('参数异常');
        info('请求成功', 1, ['self' => $self, 'ranking_list' => $list]);
    }
}