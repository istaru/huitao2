<?php
   
    class TimerTaskController{

        public $db;

        public $pdo;
       
        public $date;

        public $isDebug = 1;

        public function __construct(){
    /*
            if(substr(php_sapi_name(), 0, 3) == 'cli'){

                $arr = getopt('d:');

                $this->date = isset($arr['d']) ? $arr['d'] : date("Y-m-d");

            }else{

                $this->date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");
            }

            $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

            $this->db = $this->isDebug?"shopping_new":"huitao";*/

            load_module("record");

            $m = load_module("goods");
          
        }

        function index(){


          
        }
        //每日点击&购买数据汇集 —— 昨日数据
        public function dailyReport(){

            $r = new Record();

            $r->dailyReportRecord();
        }

        //点击记录,分享记录，搜索记录。
        public function actionRecord(){

            $r = new Record();

            $r->userActionRecord();
        }

        //更新上一小时分数，所依赖的点击&购买（计算转化率）
        public function updateScoreData(){

          //  $sql = "select sum(click),sum(pruchase) from "
            $s = new Score();
            
            $s->updateGoodsScoreInfo();

        }

        //更新上一小时分数
        public function updateGoodsScore(){
            
            //load_module("goods");
            
            $s = new Score();
            
            $s->addPurchaseRateScore();
        }

        public function orderInfo($order_list){

            $r = new Record();

            $res =  $r->updateOrderInfo($order_list);

            return $res;
        }

        public function purchaseRecord($order_id_list,$order_status=2){
           
            $r = new Record();

            $res =  $r->purchaseRecord($order_id_list,$order_status);

            //print_r($res);

            return $res;
            //echo $res;
           
            // echo $res;

        }
       
    }

             
        //$RedisCache = new RedisCacheController();
        //$RedisCache->llen("list");exit;
        //$task->fetchIdfa(452186370);
         /*$task_id = 1;

        //$task->saveTaskInfo($task_id);

        print_r($task->readTaskInfo($task_id));

        //echo $task->redis->get("task_info_1");

        $task->delTaskInfo($task_id);

        //echo $task->readTaskInfo($task_id);
        //
        //
        $task->tokenVaild("hb575002176678b",1);*/
?>