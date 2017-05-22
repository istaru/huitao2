<?php
class DailyReportController extends Controller
{

    public $st;//日期
    public $ed;//结束时间
    public $description;//备注信息

    public $newUser;
    public function __construct()
    {
        if(isset($_GET["date"])&&!empty($_GET["date"])){

           $this->st = $_GET["date"];

        } else $this->st = date("Y-m-d",strtotime("-1 day"));
        if(isset($_GET["desc"])&&!empty($_GET["desc"])){

            $this->description = $_GET["desc"];

        } else $this->description ='';

        //测试
//       $this->st='2017-05-04';

        $this->ed=  date("Y-m-d",strtotime($this->st." +1 day"));
//        echo '开始时间:'.$this->st."<br/>结束时间:".$this->ed;

    }

    public function insertdata(){
         echo "<span>查询时间:</span><input type='text' name='date' style='display: inline-block' value=".$this->st."> <br/>";
         echo "<span>添加备注:</span><span style='color: red'>".$this->description."</span><br/>";
         echo "<hr/>";

        /**
         * 安卓+IOS
         */
        echo "<span style='color: red;'>安卓+IOS:</span>(订单数据可能与分开结算之和不一致，因为有旧版用户购买)<br/>";
        //新增用户
        $sql="SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."'";
        $uid_num=self::checkvalue((M()->query($sql))['num']);
        echo "新增用户:<span style='color:red'>".$uid_num."</span><br/>";

        //新增淘宝用户
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and taobao_id is not null";
        $taobao_new_user=self::checkvalue((M()->query($sql,'single'))['num']);
        echo "新增绑定淘宝用户:<span style='color:red'>".$taobao_new_user."</span><br/>";

        //下单额 下单数 2->下单  5->退单-----》改成shopping_log 有type字段
        $sql="SELECT sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and createdAt BETWEEN '".$this->st."' and '".$this->ed."'";
        $res=M()->query($sql,'single');
        $order_fee=self::checkvalue($res['fee']);
        $order_num=self::checkvalue($res['num']);
        $benifit=self::checkvalue($res['benifit']);

        //退单数 退单额  改成shopping_log 有type字段
        $sql="SELECT sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and order_id in (select order_id from ngw_shopping_log where order_status=5 and createdAt BETWEEN '".$this->st."' and '".$this->ed."') ORDER BY order_id desc";
//        echo $sql;
        $res=M()->query($sql,'single');
        $order_back_fee=self::checkvalue($res['fee']);
        $order_back_num=self::checkvalue($res['num']);
        $f_benifit=self::checkvalue($res['benifit']);
        $order_benifit=$benifit-$f_benifit;
        echo "下单数:<span style='color:red'>".$order_num."</span>,下单额:<span style='color:red'>".$order_fee."</span>，今日利润:<span style='color:red'>".$order_benifit."</span><br/>";
        echo "退单数:<span style='color:red'>".$order_back_num."</span>,退单额:<span style='color:red'>".$order_back_fee."</span><br/>";

        //留存用户
        $sql = "select count(DISTINCT(uid)) num from ngw_uid_login_log where createdAt BETWEEN  '".$this->st."' and '".$this->ed."'";
        $user_num=self::checkvalue((M()->query($sql))['num']);
        $active_user=$user_num-$uid_num;
        echo "留存用户数:<span style='color:red'>".$active_user."</span>(总数可能会比分项小,因为用户可能同时在安卓和IOS登录)<br/>";

        //活跃用户--有点击
        $sql = "select count(DISTINCT(uid)) num from ngw_click_log where report_date = '".$this->st."'";
        $active_click_num=self::checkvalue((M()->query($sql))['num']);
        echo "活跃用户数:<span style='color:red'>".$active_click_num."</span><br/>";

        //分享总次数-未去重
        $sql = "select count(0) num from ngw_share_log where report_date = '".$this->st."'";
        $share_num=self::checkvalue((M()->query($sql))['num']);

        //分享次数-去重
        $sql = "select count(distinct(uid)) num from ngw_share_log where report_date = '".$this->st."'";
        $share_num_1 =self::checkvalue((M()->query($sql))['num']);

        //分享率
        $share_rate=number_format($share_num_1 / ($user_num+0.0001) * 100,2);  //防止为0的时候报错

        //邀请新增
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and sfuid is not null";
        $invited_user=self::checkvalue((M()->query($sql))['num']);

        echo "分享次数:<span style='color:red'>".$share_num."</span><br/>";
        echo "分享率(%):<span style='color:red'>".$share_rate."</span>(已去重，同一个用户的多次分享算一次)<br/>";
        echo "邀请新增:<span style='color:red'>".$invited_user."</span><br/>";

        //根据传的参数决定是否更新表
        if((isset($_GET["isRecord"])&&$_GET["isRecord"])){
            $sql="delete from ngw_total_daily_report where report_date = '".$this->st."' and type = 2";
            M()->exec($sql);
            $sql="insert into ngw_total_daily_report(report_date,new_user,order_num,order_sales,order_benifit,order_back,order_back_fee,active_user,share_num,share_rate,invited_user,type,new_taobao_user,description)values('".$this->st."',$uid_num,$order_num,$order_fee,$order_benifit,$order_back_num,$order_back_fee,
                $active_user,$share_num,$share_rate,$invited_user,2,$taobao_new_user,'".$this->description."')";
            $res=M()->exec($sql);
            if($res)echo "update data success(Combine)!<br/>";
        }

        /**
         * IOS用户
         */
        echo "<hr/><span style='color: red;'>IOS用户:</span><br/>";
        //新增用户
        $type_con = " and type = 0 ";
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."'".$type_con;
        $uid_num=self::checkvalue((M()->query($sql))['num']);
        echo "新增用户:<span style='color:red'>".$uid_num."</span><br/>";

        //新增淘宝用户
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and taobao_id is not null".$type_con;
        $taobao_new_user=self::checkvalue((M()->query($sql,'single'))['num']);
        echo "新增绑定淘宝用户:<span style='color:red'>".$taobao_new_user."</span><br/>";

        //下单额 下单数 2->下单  5->退单
        $sql="SELECT sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and type=2 and createdAt BETWEEN '".$this->st."' and '".$this->ed."'";
        $res=M()->query($sql,'single');
        $order_fee=self::checkvalue($res['fee']); //下单额
        $order_num=self::checkvalue($res['num']);  //下单数
        $benifit=self::checkvalue($res['benifit']);  //下单利润

        //退单数 退单额
        $sql="select sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and order_id in (select order_id from ngw_shopping_log where type=2 and order_status=5 and createdAt BETWEEN '".$this->st."' and '".$this->ed."') ORDER BY order_id desc";
        $res=M()->query($sql,'single');
        $order_back_fee=self::checkvalue($res['fee']);
        $order_back_num=self::checkvalue($res['num']);
        $f_benifit=self::checkvalue($res['benifit']);
        $order_benifit=$benifit-$f_benifit;
        echo "下单数:<span style='color:red'>".$order_num."</span>,下单额:<span style='color:red'>".$order_fee."</span>,今日利润:<span style='color:red'>".$order_benifit."</span><br/>";
        echo "退单数:<span style='color:red'>".$order_back_num."</span>,退单额:<span style='color:red'>".$order_back_fee."</span><br/>";

        //留存用户
        $sql = "select count(DISTINCT(uid)) num from ngw_uid_login_log where createdAt BETWEEN  '".$this->st."' and '".$this->ed."'" .$type_con;
        $user_num=self::checkvalue((M()->query($sql))['num']);
        $active_user=$user_num-$uid_num;
        echo "留存用户数:<span style='color:red'>".$active_user."</span><br/>";

        //活跃用户--有点击
        $sql = "select count(DISTINCT(uid)) num from ngw_click_log where report_date = '".$this->st."'".$type_con;
        $active_click_num=self::checkvalue((M()->query($sql))['num']);
        echo "活跃用户数:<span style='color:red'>".$active_click_num."</span><br/>";

        //分享总次数-未去重
        $sql = "select count(0) num from ngw_share_log where report_date = '".$this->st."'".$type_con;;
        $share_num=self::checkvalue((M()->query($sql))['num']);

        //分享次数-去重
        $sql = "select count(distinct(uid)) num from ngw_share_log where report_date = '".$this->st."'".$type_con;;
        $share_num_1 =self::checkvalue((M()->query($sql))['num']);

        //分享率
        $share_rate=number_format($share_num_1 / ($user_num+0.0001) * 100,2);  //防止为0的时候报错

        //邀请新增
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and sfuid is not null".$type_con;
        $invited_user=self::checkvalue((M()->query($sql))['num']);

        echo "分享次数:<span style='color:red'>".$share_num."</span><br/>";
        echo "分享率(%):<span style='color:red'>".$share_rate."</span>(已去重，同一个用户的多次分享算一次)<br/>";
        echo "邀请新增:<span style='color:red'>".$invited_user."</span><br/>";

        //根据传的参数决定是否更新表
        if((isset($_GET["isRecord"])&&$_GET["isRecord"])){
            $sql="delete from ngw_total_daily_report where report_date = '".$this->st."' and type = 0";
            M()->exec($sql);
            $sql="insert into ngw_total_daily_report(report_date,new_user,order_num,order_sales,order_benifit,order_back,order_back_fee,active_user,share_num,share_rate,invited_user,type,new_taobao_user,description)values('".$this->st."',$uid_num,$order_num,$order_fee,$order_benifit,$order_back_num,$order_back_fee,
                $active_user,$share_num,$share_rate,$invited_user,0,$taobao_new_user,'".$this->description."')";
            $res=M()->exec($sql);
            if($res)echo "update data success(IOS)!<br/>";
        }







        /**
         * 安卓用户
         */
        echo "<hr/><span style='color: red;'>安卓用户:</span><br/>";
        //新增用户
        $type_con = " and type = 1 ";
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."'".$type_con;
        $uid_num=self::checkvalue((M()->query($sql))['num']);
        echo "新增用户:<span style='color:red'>".$uid_num."</span><br/>";

        //新增淘宝用户
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and taobao_id is not null".$type_con;
        $taobao_new_user=self::checkvalue((M()->query($sql,'single'))['num']);
        echo "新增绑定淘宝用户:<span style='color:red'>".$taobao_new_user."</span><br/>";

        //下单额 下单数 2->下单  5->退单
        $sql="SELECT sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and type=1 and createdAt BETWEEN '".$this->st."' and '".$this->ed."'";
        $res=M()->query($sql,'single');
        $order_fee=self::checkvalue($res['fee']); //下单额
        $order_num=self::checkvalue($res['num']);  //下单数
        $benifit=self::checkvalue($res['benifit']);  //下单利润

        //退单数 退单额
        $sql="select sum(benifit) benifit,sum(fee) fee,count(0) num from ngw_shopping_log where order_status=2 and order_id in (select order_id from ngw_shopping_log where type=1 and order_status=5 and createdAt BETWEEN '".$this->st."' and '".$this->ed."') ORDER BY order_id desc";
        $res=M()->query($sql,'single');
        $order_back_fee=self::checkvalue($res['fee']);
        $order_back_num=self::checkvalue($res['num']);
        $f_benifit=self::checkvalue($res['benifit']);
        $order_benifit=$benifit-$f_benifit;
        echo "下单数:<span style='color:red'>".$order_num."</span>,下单额:<span style='color:red'>".$order_fee."</span>,今日利润:<span style='color:red'>".$order_benifit."</span><br/>";
        echo "退单数:<span style='color:red'>".$order_back_num."</span>,退单额:<span style='color:red'>".$order_back_fee."</span><br/>";

        //留存用户
        $sql = "select count(DISTINCT(uid)) num from ngw_uid_login_log where createdAt BETWEEN  '".$this->st."' and '".$this->ed."'" .$type_con;
        $user_num=self::checkvalue((M()->query($sql))['num']);
        $active_user=$user_num-$uid_num;
        echo "留存用户数:<span style='color:red'>".$active_user."</span><br/>";

        //活跃用户--有点击
        $sql = "select count(DISTINCT(uid)) num from ngw_click_log where report_date = '".$this->st."'".$type_con;
        $active_click_num=self::checkvalue((M()->query($sql))['num']);
        echo "活跃用户数:<span style='color:red'>".$active_click_num."</span><br/>";

        //分享总次数-未去重
        $sql = "select count(0) num from ngw_share_log where report_date = '".$this->st."'".$type_con;;
        $share_num=self::checkvalue((M()->query($sql))['num']);

        //分享次数-去重
        $sql = "select count(distinct(uid)) num from ngw_share_log where report_date = '".$this->st."'".$type_con;;
        $share_num_1 =self::checkvalue((M()->query($sql))['num']);

        //分享率
        $share_rate=number_format($share_num_1 / ($user_num+0.0001) * 100,2);  //防止为0的时候报错

        //邀请新增
        $sql = "SELECT count(0) num from ngw_uid where createdAt BETWEEN '".$this->st."' and '".$this->ed."' and sfuid is not null".$type_con;
        $invited_user=self::checkvalue((M()->query($sql))['num']);

        echo "分享次数:<span style='color:red'>".$share_num."</span><br/>";
        echo "分享率(%):<span style='color:red'>".$share_rate."</span>(已去重，同一个用户的多次分享算一次)<br/>";
        echo "邀请新增:<span style='color:red'>".$invited_user."</span><br/>";

        //根据传的参数决定是否更新表
        if((isset($_GET["isRecord"])&&$_GET["isRecord"])){
            $sql="delete from ngw_total_daily_report where report_date = '".$this->st."' and type = 1";
            M()->exec($sql);
            $sql="insert into ngw_total_daily_report(report_date,new_user,order_num,order_sales,order_benifit,order_back,order_back_fee,active_user,share_num,share_rate,invited_user,type,new_taobao_user,description)values('".$this->st."',$uid_num,$order_num,$order_fee,$order_benifit,$order_back_num,$order_back_fee,
                $active_user,$share_num,$share_rate,$invited_user,1,$taobao_new_user,'".$this->description."')";
            $res=M()->exec($sql);
            if($res)echo "update data success(Android)!<br/>";
        }


    }
    public static  function checkvalue($value){
        return $value=$value?$value:0;
    }

}