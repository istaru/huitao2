<?php
class HtUserController {
    /**
     * [getInfo  获取所有app用户信息]
     */
    public function queryusers()
    {
        $data = A('HtUser:queryUsers',[$_POST]);
        $data ? info(1, 'ok', $data) : info('暂无数据',-4);

    }
    /**
     * 获取后台用户的用户信息
    */
    public function querybackuser()
    {
        $data = A('HtUser:queryBackUsers',[$_POST]);
        $data ? info(1, 'ok', $data) : info('暂无数据',-4);

    }
    /**
     * [doLogin  用户登录]
      */
   public function  dologin(){
        if(I('username') && I('password')) {
//            $user = A('HtUser:getLoginInfo',[I('username'),I('password')]);
            $user=M()->query("select id,username,password from gw_htuser where `username`='".I('username')."' and password='".I('password')."'");
            if($user) {
                session_unset();
                $_SESSION['user'] = $user;
                info('登录成功',1);
            } else {
                info('用户名或密码错误，请重新输入!',-1);
            }
        }
      }
    /**
     * [getUserInfo 用户个人中心]
     */
    public function getuserinfo()
    {
        empty($_SESSION['user']) ? info('您还未登录',-3) : info('OK',1,$_SESSION['user']);
    }
    /**
     * [exitLogin 退出登录]
     */
    public function exitlogin()
    {
        session_unset();
        session_destroy();
    }
    /**
     * [upUserInfo 用户个人中心修改]
     */
    public function upuserinfo()
    {
        I('id') or info('缺少唯一标识',-1);
        $id = I('id');
        unset($_POST['id']);
        $res = A('HtUser:upUserInfo',[$_POST]);
        $res ? info('修改成功',1) : info('修改失败',-1);
    }

    //升级为特邀用户
    public function updata() {
        if($_POST['uid']) {
            M('uid')->where(['objectId' => ['=', $_POST['uid']]])->save(['power' => 2]);
            info('修改成功',1);
        }
        info('缺少参数',-1);
    }
    public function menu()
    {

    }

}