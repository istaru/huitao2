<?php
/**
 * 淘宝登录授权认证
 */
class TaoBaoAuthController extends AppController
{
    public function authInfo()
    {
        $data = $this->dparam;
        //取消授权
        if(empty($data['taobao_id']) && !empty($data['user_id'])) {
            M('uid')->where(['objectId' => ['=',$data['user_id']]])->save([
                'taobao_auth' => 0,
                'nickname'    => $data['user_id'],
                'head_img'    => RES_SITE."shoppingResource/head/".rand(1,2).".jpg",
            ]) ? info('已成功取消授权',1) : info('取消授权失败',-1);
        //授权认证
        } else if(!empty($data['user_id']) && !empty($data['taobao_id']) && !empty($data['user_name']) && !empty($data['user_head_img']) && !empty($data['imei']) || !empty($data['bdid']) || !empty($data['idfa'])) {
            //设置某些参数默认值 然后合并覆盖
            $arr = ['bdid' => '', 'uuid' => '', 'idfa' => '', 'imei' => ''];
            $data = array_merge($arr, $data);
            try {
                M()->startTrans();
                //查询该淘宝ID信息 是否已经存在taobao表中 不存在添加淘宝id信息
                if(!M('taobao')->where(['taobao_id' => ['=', $data['taobao_id']]])->select('single')) {
                    M('taobao')->add([
                        'taobao_id' => $data['taobao_id'],
                        'nick'      => $data['user_name']
                    ]) OR E('授权失败');
                }
                //获取用户设备表主键id
                $didId = (M('did_log')->where("(bdid='{$data['bdid']}' OR idfa='{$data['idfa']}' AND uuid='{$data['uuid']}') OR (imei='{$data['imei']}')")->field('id')->select('single'))['id'] ? : E('未能获取到您的设备信息');
                //绑定淘宝账号与用户之间的关系 如果之前绑定过一样的则不需要再添加
                if(!M('taobao_log')->where(['uid' => ['=', $data['user_id']], 'taobao_id' => ['=', $data['taobao_id']]],['and'])->select('single')) {
                    M('taobao_log')->add([
                        'taobao_id'   => $data['taobao_id'],
                        'uid'         => $data['user_id'],
                        'report_date' => date('Y-m-d'),
                        'did_id'      => $didId
                    ]) OR E('授权失败');
                }
               //修改用户授权状态 用户头像 用户昵称
                M('uid')->where(['objectId' => ['=',$data['user_id']]])->save([
                    'taobao_auth'   => 1,
                    'head_img'      => $data['user_head_img'],
                    'nickname'      => $data['nick']
                ]) OR E('授权失败');
            } catch(Exception $e) {
                M()->rollback();
                info($e->getMessage(), -1);
            }
            M()->commit();
            //分享链接过来的用户如果还没绑定好友关系的 就去绑定
            if($name = M()->query("SELECT * FROM gw_friend_log WHERE phone = (select phone from gw_uid where objectId='{$data['user_id']}') AND status=1", 'single'))
                !(new UserController)->bindMasters(true, $data['user_id'], $name['sfuid']) OR M('friend_log')->where(['phone' => ['=', $name['phone']]])->save(['status' => 2]);

            info('授权成功',1);
        }
        info('缺少参数',-1);
    }
}