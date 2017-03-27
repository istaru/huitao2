<?php

header("Content-type: text/html; charset=utf-8");
/*
记录类
 */
class RecordController extends Controller{

    public $redis;
    //
    public $request;

    public $daily_purchase_log_table = "gw_click_log";

    public $goods_daily_report_table = "gw_goods_daily_report";

    public $goods_shopping_log_table = "gw_shopping_log";

    public $date;

    public $isDebug = 0;

    public $pdo;

    public $db;

    public  function __construct(){

       include_once("RedisCacheController.php");

       $this->redis = new RedisCacheController();

       $this->request = new Request;

       //print_r($this->request->param);

       $this->date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");

       $this->pdo = $this->isDebug?jpLaizhuanCon("shopping"):shoppingCon();

       $this->db = $this->isDebug?"shopping":"huitao";
    }

    //redis存入daily_uid_goods_report表
    //点击商品详情时候记录（外部调用）
    //
    public function clickRecord($uid=null,$item_id=null){
        //print_r($_REQUEST["uid"]);
        if(isset($_REQUEST["uid"])&&!empty($_REQUEST["uid"])){
            $uid = $_REQUEST["uid"];
        }
        //$uid = isset($_REQUEST["uid"])?$_REQUEST["uid"]:$uid;

        if(isset($_REQUEST["num_iid"])&&!empty($_REQUEST["num_iid"])){
           $item_id = $_REQUEST["num_iid"];
        }

        //$item_id = isset($_REQUEST["num_iid"])?$_REQUEST["num_iid"]:$item_id;

        if($uid&&$item_id){

            $data = array("report_date"=>date("Y-m-d"),"num_iid"=>$item_id,"uid"=>$uid,"click"=>1,"createdAt"=>date("Y-m-d H:i:s"));

            $rt = $this->redis->insertUidClickData($data);
            //返回值小于0,直接记录进数据库
            if(!$rt||$this->redis==null){


                /*
                list($sql,$insert_data) = fetchInsertMoreSql("gw_daily_uid_goods_report",array_keys($data),$click_data,false,$this->pdo);


               // echo $sql;print_r($insert_data);//exit;

                $rt = db_execute($sql,"shopping",$insert_data,$this->pdo);
            */
            }
        }else {
            echo date("Y-m-d H:i:s")."param miss.";exit;
        }

    }
    //定时任务1
    //定时的存取redis的到gw_daily_uid_goods_report
    //①读redis存click，②读shopping_log存purchase
    public function loopDailyUidGoodsReport(){
        //定时的存取redis的到gw_daily_uid_goods_report
        $rt = $this->redis->readUidActInfo();
        if(!$rt){
            echo date("Y-m-d H:i:s")."read redis fail.";exit;
        }
    }
    //手动导入数据
    public function inputDailyUidGoodsReport($date){
       
        $rt = $this->redis->readUidActInfo(2000,$date);
        if(!$rt){
            echo date("Y-m-d H:i:s")."read redis fail.";//exit;
        }

    }
    //自动循环补全订单数据
    public function fillOrderIndo(){

        $date = $this->date;
        //$date = '2017-01-17';
        $sql = "select distinct(order_id) from (
                SELECT a.order_id,b.auction_title,b.open_id from gw_order a LEFT JOIN gw_order_status b on a.order_id = b.order_id
                where a.status = 0 and b.status = 2 and a.created_date  = '".$date."'
                )a join gw_goods_online b on a.auction_title = b.title or a.open_id = b.num_iid";

        $order_list = db_query_col($sql,$this->db,array(),$this->pdo);
        //echo $sql;print_r($order_list);
        $this->updateOrderInfo($order_list);
    }

    //
    //购物消息队列后的补全gw_order表的数据（外部调用）

    public function updateOrderInfo($order_list=array()){
        //$order_list = array(3029595814303222);
        $sql = "select distinct(order_id) from (
                SELECT a.order_id,b.auction_title,b.open_id from gw_order a LEFT JOIN gw_order_status b on a.order_id = b.order_id
                where a.status = 0 and b.status = 2 and a.created_date BETWEEN '".date("Y-m-d",strtotime($this->date) - 6*3600*24)."' and '".$this->date."'
                 and a.order_id in(".implode(",",$order_list).")
                 )a join gw_goods_online b on a.auction_title = b.title or a.open_id = b.num_iid";
       // echo $sql;exit;
        $id_list = db_query_col($sql,$this->db,array(),$this->pdo);

        if(!count($id_list))return;

        $sql_list = array();

        $sql_list[] = "insert into gw_order(status,cost,amount,uid,taobao_nick,createdAt,created_date,order_id,num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,
                rating,seller_id,seller_name,store_name,store_type,top,taobao_cid,gw_pid,gw_id,gw_name,sum,num,val,limited,reduce,discount,
                deal_price,start_time,end_time,url,coupon_url
                )SELECT 1,a.cost,a.amount,a.uid,a.taobao_nick,a.createdAt,a.created_date,order_id,num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,
                rating,seller_id,seller_name,store_name,store_type,top,taobao_cid,gw_pid,gw_id,gw_name,sum,num,val,limited,reduce,discount,
                deal_price,start_time,end_time,url,coupon_url from
                (SELECT auction_title,uid,taobao_nick,a.createdAt,a.created_date,b.order_id,(paid_fee/auction_amount)cost,auction_amount amount,b.open_id from gw_order a LEFT JOIN gw_order_status b on a.order_id = b.order_id

                 where a.status = 0 and b.status = 2 and a.created_date BETWEEN '".date("Y-m-d",strtotime($this->date) - 6*3600*24)."' and '".$this->date."' and a.order_id in(".implode(",",$id_list).") GROUP BY a.order_id) a

            LEFT JOIN gw_goods_online b on a.auction_title = b.title or a.open_id = b.num_iid group by a.order_id";
            //!!*顺序
        $sql_list[] = "delete from gw_order where order_id in(".implode(",",$id_list).") and status = 0";
        //print_r($sql_list);exit;//2930383815453222 2937120419083222
        $rt = db_transaction($this->pdo, $sql_list);

        if($rt){
            //支付成功后,直接插入数据库表 gw_shopping_log
            $this->purchaseRecord($id_list,2);

            echo date("Y-m-d H:i:s").":transcation daily purchase success.\r\n";
        }
        else {
            echo date("Y-m-d H:i:s").":transcation daily purchase fail.\r\n";
            exit;
        }

    }

    public function updateOrderBack($order_list=array()){

       // $order_list = array(3081220024703900);

        foreach ($order_list as $key => $value) {
            
            $sql = "update gw_order set order_status = 5 where order_id = ".$value;

            $rt = db_execute($sql,$this->db,array(),$this->pdo);
        }

    }


    //支付成功后,直接插入数据库表 gw_shopping_log
    //退款后，直接插入数据库表（受外部调用）
    public function purchaseRecord($order_id_list=array(),$order_status=2){
        //$order_id_list = array(3028441002495722);
        if(is_array($order_id_list)&&count($order_id_list)){

    //        $order_id_list = array(2937120419083222,2930383815453222,2930383815453223);

           //退单，买单都是必须存在的order信息，顺带记录进去 扣除邮费的成交价和利润
            /*$sql = "insert into gw_shopping_log(status,uid,taobao_nick,fee,order_id,order_status,num_iid,report_date)
            select 1,uid,taobao_nick,deal_price fee,order_id,".$order_status.",num_iid,'".$this->date."' report_date from gw_order
                where order_id in(".implode(",",$order_id_list).")";
            */
             $sql = "insert into gw_shopping_log(status,uid,taobao_nick,fee,benifit,type,order_id,order_status,num_iid,report_date)
            select 1,uid,taobao_nick,cost*amount fee,cost*amount*rating/100 benifit,type,order_id,".$order_status.",num_iid,'".$this->date."' report_date from gw_order
                where order_id in(".implode(",",$order_id_list).")";
           //echo $sql;exit;
            /*
            $attrs = array("num_iid","uid","did","taobao_id","fee","order_id","order_status","pay_callback","pay_time");

            $params = $this->request->filter($attrs);

            $params["report_date"] = date("Y-m-d");

            list($sql,$insert_data)= fetchInsertMoreSql($this->goods_shopping_log_table,array_keys($params),array($params),false,"gw");

            //echo $sql;
            */
            $rt = db_execute($sql,$this->db,array(),$this->pdo);
            //$rt = db_execute($sql,"gw",$insert_data,shoppingCon());
            // sreturn($data=array($rt));

        }

    }
    /*
    //读购物行为 shopping_log -> daily_goods_report
    public function readUidPurchaseInfo(){

        $sql_list[] = "delete from gw_goods_daily_report where report_date = '".$this->date."' and click = 0";

        //购买成功的购买数据汇集，按商品聚合
        $sql_list[] = "insert gw_goods_daily_report(report_date,num_iid,click,purchase,order_status)
            select report_date,num_iid,0,count(num_iid) purchase,order_status from gw_shopping_log
            where order_status = 2 and report_date = '".$this->date."'
            GROUP BY num_iid";


    }
    */

    //合并2种数据到daily_report
    //
    public function dailyReportRecord($date=null){

        echo date("Y-m-d H:i:s")." - start:"."\r\n";

        //echo "click data record."."\r\n";

        $this->_dailyReportByClick($date);

        //echo "purchase data record."."\r\n";

        $this->_dailyReportByPurchase($date);


    }


    //购买数据汇集 daily_goods_report -> daily_report
    public function _dailyReportByPurchase($date=null){

        if(!$date)$date = $this->date;

        //$date = "2016-12-14";

        $sql_list[] = "delete from gw_goods_daily_report where report_date = '".$date."' and click = 0";

        //购买成功的购买数据汇集，按商品聚合
        $sql_list[] = "insert gw_goods_daily_report(report_date,num_iid,click,purchase,order_status)
            select report_date,num_iid,0,count(num_iid) purchase,order_status from gw_shopping_log
            where order_status = 2 and report_date = '".$date."'
            GROUP BY num_iid";

        //购买未成功的购买数据汇集，按商品聚合 order_status != 2
        $sql_list[] = "insert gw_goods_daily_report(report_date,num_iid,click,purchase,order_status)
            select report_date,num_iid,0,count(num_iid) purchase,order_status from gw_shopping_log
            where order_status <> 2 and report_date = '".$date."'
            GROUP BY num_iid";

        $rt = db_transaction($this->pdo, $sql_list);

        if($rt)//echo date("Y-m-d H:i:s").":transcation daily purchase success.\r\n";
            echo "Psuccess.\r\n";
        else {
            //echo date("Y-m-d H:i:s").":transcation daily purchase fail.\r\n";
            echo "Pfail.\r\n";
            exit;
        }


    }


    //点击数据汇集 daily_goods_report -> daily_report
    public function _dailyReportByClick($date=null){

        if(!$date)$date = $this->date;

        $sql_list[] = "delete from gw_goods_daily_report where report_date = '".$date."'  and purchase = 0";

        $sql_list[] = "insert gw_goods_daily_report(report_date,num_iid,click,purchase,order_status)
            select report_date,num_iid,sum(click) click,0,2 from gw_click_log
              where report_date = '".$date."' GROUP BY num_iid";

        $rt = db_transaction($this->pdo, $sql_list);

        if($rt)//echo date("Y-m-d H:i:s").":transcation daily click success.\r\n";
            echo "Csuccess.\r\n";

        else {
            //echo date("Y-m-d H:i:s").":transcation daily click fail.\r\n";
            echo "Cfail.\r\n";
            exit;
        }


    }




}


