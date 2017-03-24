<?php

header("Content-type: text/html; charset=utf-8");


class ShareReportController extends Controller{

    public $sdate;

    public $edate;

    public $platform_type_con="";

    public function __construct(){

        if(isset($_REQUEST["sdate"])&&!empty($_REQUEST["sdate"])){

            $this->sdate = $_REQUEST["sdate"];

        }

        if(isset($_REQUEST["edate"])&&!empty($_REQUEST["edate"])){

            $this->edate = $_REQUEST["edate"];

        }

        if(!$this->edate)$this->edate = date("Y-m-d");

        if(!$this->sdate)$this->sdate = date("Y-m-d",strtotime($this->edate." -7 day"));

    if(isset($_REQUEST["platform_type"])&&($_REQUEST["platform_type"]!=='')&&in_array($_REQUEST["platform_type"],array(0,1))){

           // $this->platform_type = $_REQUEST["platform_type"];

            $this->platform_type_con = ' and type = '.$_REQUEST["platform_type"];
          
        }else $this->platform_type_con = ' and type = 2';


    }

    public function draw(){
        $this->view('index');
    }
    public function index(){
        //!!*缺用户徒弟数
        $sql = "select uid,share_type,count(num_iid) shares from gw_share_log where report_date BETWEEN '2017-01-16' and '2017-02-10' GROUP BY uid,share_type";

    }

    public function total_report_chart(){

        $sql = "select report_date,new_user,order_num,order_sales,order_benifit,order_back,order_back_fee,active_user,share_num,share_rate,invited_user from gw_total_daily_report where report_date between '".$this->sdate."' and '".$this->edate."'".$this->platform_type_con.' order by report_date asc';
        //echo $sql;exit;
        $result = db_query($sql,"",array(),shoppingCon());

        //print_r($result);

        $chart = array();
        //类目题目
        //


        $chart_ref = array(

            "new_user"=>"新增用户","order_num"=>"下单数","order_sales"=>"下单金额","order_benifit"=>"下单利润","order_back"=>"退单数",

            "order_back_fee"=>"退单金额","active_user"=>"留存用户","share_num"=>"分享数","share_rate"=>"分享率","invited_user"=>"邀请新增");

        $chart["legend"] = array_values($chart_ref);

        $data = array();
        if($result){
            foreach ($result as $key => $value) {
                //x轴只传数据
                $chart["xAxis"]["data"][] = $value["report_date"];


                foreach ($value as $k => $v) {

                    $data[$k][] = $v;
                }

                //$chart["series"]["type"] = ;

                //$chart["series"]["stack"] = ;
                //y轴数据

            }
            // print_r($data);exit;

            $chart["series"] = array();

            foreach ($chart_ref as $attr=>$name) {

                $chart["series"][] = array("name"=>$name,"type"=>"line","data"=>$data[$attr]);

            }
            // print_r($chart);
            //测试loading动画
             sleep(1);
            info('ok',1,$chart);
        }else{
            info('暂无数据',-1);
        }
    }


}


