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
    public function dataList($objectId = 0,$score)
    {
        $infoList = [];
        foreach($score as $k => $v) {
            if($v['score_source'] == $objectId) {
                unset($v['score_source']);
                $infoList[] = $v;
            }
        }
        return $infoList;
    }
    /**
     * [invitateInfo 全部用户--好友邀请排行榜排名]
     */
    public function invitateInfo()
    {
        /**
         *  获取到该用户邀请的好友给他带来的收入情况 以及邀请的人数
         */
        if(!empty($this->dparam['user_id'])) {
            $sql = "SELECT * FROM( SELECT sum(price) money , uid FROM gw_uid_log WHERE STATUS IN(1 , 2) AND uid = '{$this->dparam['user_id']}') a LEFT JOIN( SELECT count(0) person_num , uid FROM gw_uid_log WHERE score_type = 2 AND uid = '{$this->dparam['user_id']}') b ON b.uid = a.uid";
            $data = M()->query($sql,'single');
        } else {
            $data['money']      = 0;
            $data['person_num'] = 0;
        }
        /**
         * 好友邀请排行榜排名
         */
        $sql = 'select a.uid,price as price,friends_num,name,head_img from
    (select sum(price) price ,uid from gw_uid_log where status in(1,2) group by uid) a
JOIN
    (select count(id) friends_num ,uid from gw_uid_log where score_type = 2 group by uid) b on a.uid =b.uid
JOIN
    (select nickname as name,head_img as head_img,objectId from gw_uid) c on  c.objectId=a.uid order by a.price desc limit 10';
        $desc = M()->query($sql,'all');
        $result = [
            'status'       => 1,
            'msg'          => '请求成功',
            'money'        => $data['money'],
            'person_num'   => $data['person_num'],
            'ranking_list' => $desc
        ];
        info($result);
    }
    //排行榜
    public function rankingList() {
        !empty($this->dparam['type']) or $this->dparam['type'] = 1;
        switch ($this->dparam['type']) {
            //邀请榜
            case 1:
                $sql = 'SELECT invitation_count friends_num , nickname , head_img FROM gw_uid ORDER BY invitation_count DESC LIMIT 10';
                break;
            //收入榜
            case 2:
                // $sql = 'SELECT price, objectId uid, head_img, nickname FROM gw_uid ORDER BY price DESC LIMIT 10';
                $sql = 'SELECT * FROM( SELECT sum(price) friends_num , uid FROM gw_uid_log WHERE status = 2 GROUP BY uid LIMIT 10) a LEFT JOIN( SELECT nickname , head_img , objectId FROM gw_uid) b ON b.objectId = a.uid ORDER BY friends_num DESC';
                break;
            //任务榜
            case 3:
                $sql = 'SELECT a.friends_num , a.uid , b.head_img , b.nickname FROM( SELECT count(0) friends_num , uid FROM gw_task GROUP BY uid DESC LIMIT 10) a LEFT JOIN( SELECT head_img , nickname , objectId FROM gw_uid) b ON b.objectId = a.uid ORDER BY friends_num';
                break;
        }
        info('ok', 1, M()->query($sql, 'all'));
    }
}