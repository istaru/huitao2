<?php
class InvitationsController extends AppController
{
    /**
     * [invitations 邀请好友列表以及好友带来的收入]
     */
    public function invitations()
    {
        !empty($this->dparam['user_id']) or info('缺少用户id');
        $sql = "SELECT uid.head_img,uid.nickname,b.score_source,b.score_info msg,b.createdAt date_time FROM (select * from gw_uid_log where uid = '{$this->dparam['user_id']}' AND score_type = 2) b JOIN gw_uid uid ON uid.objectId=b.score_source";
        $sql1 = "SELECT uid.head_img,uid.nickname,b.score_source,b.score_info msg,b.createdAt date_time FROM (select * from gw_income_log where uid='{$this->dparam['user_id']}') b JOIN gw_uid uid ON uid.objectId=b.score_source";
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
        $parmas = $_POST;
        //查看用户自己的收入(包含预估收入)以及邀请人数
        $self = empty($parmas['user_id']) ? ['money' => 0, 'person_num' => 0] : M()->query("SELECT a.money,b.person_num FROM( SELECT sum(price) money , uid FROM gw_uid_log WHERE status IN(1 , 2) AND uid = '{$parmas['user_id']}') a LEFT JOIN( SELECT count(0) person_num , uid FROM gw_uid_log WHERE score_type = 2 AND uid = '{$parmas['user_id']}') b ON b.uid = a.uid");
       $startTime = $endTime = '';
       //月榜
       if(isset($parmas['query']) && $parmas['query'] == 'month') {
            $startTime = date('Y-m-d 00:00:00', strtotime(date('Y-m')));
            $endTime   = date('Y-m-d 24:00:00', strtotime(date('Y-m-t')));
        //周榜
       } else if(isset($parmas['query']) && $parmas['query'] == 'week') {
            $startTime = date('Y-m-d 00:00:00', strtotime('+'. 1 - date('w') .' days' ));
            $endTime   = date('Y-m-d 24:00:00', strtotime('+'. 7 - date('w') .' days' ));
        }
        //type 1 = 收入榜, 2 = 邀请榜
        if($parmas['type'] == 1) {
            $list = $this->queryRankingList($startTime, $endTime);
        } else if($parmas['type'] == 2)
            $list = M()->query('SELECT b.nickname , b.head_img, a.friends_num FROM( SELECT count(0) friends_num , uid FROM gw_uid_log WHERE score_type = 2 GROUP BY uid ORDER BY friends_num DESC LIMIT 10) a LEFT JOIN( SELECT nickname , head_img , objectId FROM gw_uid) b ON b.objectId = a.uid', 'all');
        else info('参数异常');
        info('请求成功', 1, ['self' => $self, 'ranking_list' => isset($list) ? $list : []]);
    }
    // 所选日期内查询所有用户收入
    public function queryRankingList($startTime = '', $endTime = '') {
        $str = '';
        if($startTime && $endTime)
            $str = "AND createdAt >= '{$startTime}' AND  createdAt < '{$endTime}'";
        return M()->query('SELECT b.nickname , b.head_img, a.friends_num FROM( SELECT sum(price) friends_num , uid FROM gw_uid_log WHERE status = 2 ' .$str. ' GROUP BY uid ORDER BY friends_num DESC LIMIT 10) a LEFT JOIN( SELECT nickname , head_img , objectId FROM gw_uid) b ON b.objectId = a.uid', 'all');
    }
}