<?php
   
    class RedisCacheController{

        public $task_info_key = "task_info_";

        public $app_info_key = "app_info_";

        public $report_data_key = "report_info_";

        public $log_data_key = "log_info_";

        public $affiliate_key = "affiliate_info_";

        public $idfa_key = "idfa_info_";

        public $idfa_new_key = "idfa_new_info_";

        public $redis;

        public $task_callback_key = "task_callback_";

        public $db;

        public $pdo;

        public $click_data_key = "click_data_";

        public $purchase_date_key = "purchase_data_";

         public $isDebug = 0;

        //默认过期时间 7天
        public $expire_cyc;

        function __construct(){
           // echo 1;
            //$this->pdo = jpLaizhuanCon("shopping");

            //$this->db = "jpItem";

            //$this->date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");

            $this->pdo = $this->isDebug?jpLaizhuanCon("shopping"):shoppingCon();

            $this->db = $this->isDebug?"shopping":"huitao";
            // echo 2;
            $this->expire_cyc = 3600 * 24 * 7;

            $this->redis = $this->connectRedis();

            if(!$this->redis){

                system("/usr/bin/redis-server");

                $this->redis = $this->connectRedis();
            
            }//redis挂了
            if(!$this->redis)echo "redis down...";

           // print_r($this->redis);
        }

        public function connectRedis(){

            if($this->redis)return $this->redis;

            $this->redis = new redis(); 
            
            $r = $this->redis->connect('127.0.0.1', 6379); 

            if(!$r)return null;

            return $this->redis;

        }

        //记录用户点击信息
        //uid:用户id
        //item_id:商品id
        //action:动作0-click 1-purchase
        public function readUidActInfo($length=2000,$date=null){

             if(!$date)$date = date("Y-m-d");
                //echo 234324;
             $datas = $this->redis->lrange($this->click_data_key.$date,0,$length-1);
             
             $start = count($datas);
             //echo $start;
             if($start){

                $click_data = array();
                //print_r($datas);
                foreach ($datas as $k => $v) {
                     
                    $click_data[] = json_decode($v,true);

                }    
                //print_r($click_data);exit;
                //批量插入
                if(!is_array(array_keys($click_data[0]))){
                    exit;
                };

                list($sql,$insert_data) = fetchInsertMoreSql("gw_click_log",array_keys($click_data[0]),$click_data,false,$this->pdo);  

                
               // echo $sql;print_r($insert_data);//exit;

                $rt = db_execute($sql,$this->db,$insert_data,$this->pdo); 
               // echo $rt;
                if($rt){     echo $rt;
                    //清掉这部分内存
                    $r = $this->redis->ltrim($this->click_data_key.$date,$start,-1);
                   
                    if($r<=0)return $r;//xreturn("clear redis ".$this->click_data_key.date("Y-m-d")." unvalid.");

                    else return $r;
                }
            }
            else {
                echo "no data.";
                exit;
            }
        }

        //插入点击数据
        public function insertUidClickData($data){

            //将用户数据存入LIst的末尾。
            //<report_date,uid,num_iid,click,createdAt>
            return $this->redis->rpush($this->click_data_key.date("Y-m-d"),json_encode($data));
        }

        public function insertUidPurchaseData($data){
            //将用户数据存入LIst的末尾。
            //<report_date,uid,num_iid,click,createdAt>
            $this->redis->rpush($this->purchase_date_key.date("Y-m-d"),json_encode($data));
        }


        //记录任务信息到redis
        public function saveTaskInfo($app_id,$aff_id){

            $sql = "select * from ofr_task2 where storeid = ? and affiliate_id = ? order by id desc limit 1";

            $task_info =  db_query_row($sql,"taskofr",array($app_id,$aff_id));
            //如果是空数据，该任务不存在
            if(!isset($task_info["id"])||empty($task_info["id"])){

                $this->redis->set($this->app_info_key.$app_id."_".$aff_id,0);
                //保存时间设置 30s
                $this->redis->expire($this->app_info_key.$app_id."_".$aff_id,10);
                
                return 0;
            }

            else $task_id = $task_info["id"];
            //print_r($task_info);
            //存任务信息
            $this->redis->set($this->task_info_key.$task_id,json_encode($task_info));
            //存渠道 app映射的任务号
            $this->redis->set($this->app_info_key.$app_id."_".$aff_id,$task_id);
            //超时设置 3600 * 24 * 7
            //$this->redis->expire($this->task_info_key.$task_id,$this->expire_cyc);
            return $task_id;

        }

        //根据任务id读取任务信息
        public function readTaskInfo($app_id,$aff_id){
            //读对应的任务号
            $task_id = $this->redis->get($this->app_info_key.$app_id."_".$aff_id);
            //任务号从内存读出是0
            if(!is_null($task_id)&&$task_id==0)return 0;

            $infos = $this->redis->get($this->task_info_key.$task_id);
            //没有值，存值
            if(!$infos){

                $task_id = $this->saveTaskInfo($app_id,$aff_id);

                $infos = $this->redis->get($this->task_info_key.$task_id);

            }
            //不存在这个任务 直接返回,存在 就解锁后返回
            if($infos!=0){

                $infos = json_decode($infos,true);

                if(!$infos){
                    $this->redis->del($this->task_info_key.$task_id);
                    echo "infos convert json failure.";
                }

            }

            return $infos;

        }

   


        //读写reids
        public function redis($key,$val=""){

            if($val)$this->redis->set($key,$val);

            else return $this->redis->get($key);

        }



        public function delTaskInfo($task_id){

             $this->redis->del($this->task_info_key.$task_id);

        }

        
        public function insertReportData($data){

            //将data json存入当天report的末尾。
            $this->redis->rpush($this->report_data_key.date("Y-m-d"),json_encode($data));


        }

        public function readReportData(){

            //将data json存入当天report的末尾。
            return $this->redis->lrange($this->report_data_key.date("Y-m-d"),0,-1);

            
        }

        public function insertCallbackData($transcation){

            //将data json存入当天callback的末尾。
            $this->redis->rpush($this->task_callback_key.date("Y-m-d"),$transcation);


        }

        public function readCallbackData(){

            //将data json存入当天callback的末尾。
            return $this->redis->lrange($this->task_callback_key.date("Y-m-d"),0,-1);

            
        }


         public function insertLogData($data){

            //将data json存入当天report的末尾。
            $this->redis->rpush($this->log_data_key.date("Y-m-d"),json_encode($data));


        }

        public function readLogData(){

            //将data json存入当天report的末尾。
            return $this->redis->lrange($this->log_data_key.date("Y-m-d"),0,-1);

            
        }

        //从redis的list存入数据
        public function loopSaveLogData($length=3){

             $datas = $this->redis->lrange($this->log_data_key.date("Y-m-d"),0,$length-1);
             
             $start = count($datas);

             if($start){

                $c = array();
                 
                foreach ($datas as $k => $v) {
                     
                    $data = json_decode($v,true);

                    $name = $data["name"];

                    unset($data["name"]);
                    //print_r($data);
                    if(isset($c[$name]))$c[$name] .= json_encode($data) . "\r\n\r\n";

                    else $c[$name] = json_encode($data) . "\r\n\r\n";

                }

                $path = "/home/wwwroot/php7/domain/es2.laizhuan.com/web/hb_admin/module/offer/log/".date("Y-m-d");
                 
                if(!is_readable($path)) {  
                    
                    is_file($path) or mkdir($path,0777);  
                }  
               
                foreach ($c as $name=>$file) {
                        
                    echo file_put_contents($path."/".$name.".txt", $file, FILE_APPEND);

                }
                
                //清掉这部分内存
                $r = $this->redis->ltrim($this->log_data_key.date("Y-m-d"),$start,-1);
               
                if($r<=0)xreturn("clear redis ".$key.date("Y-m-d")." unvalid.");

                else echo $r;
            }
            /**/
        }
        //提取redis里面报表数据入库
        public function loopSaveReportData($length=3){
         
            $datas = $this->redis->lrange($this->report_data_key.date("Y-m-d"),0,$length-1);
            
            $start = count($datas);

            if($start){
            
                $c = array();
              
                $attr_key = array("affiliate_id","appid","idfa","ip","did","tid","createdAt","action_type","url","post","request_log","created_time","callback","transcation");

                $attr_val = array();

                foreach ($datas as $k => $v) {
                     
                    $data = json_decode($v,true);

                    if(!isset($data["createdAt"]))$data["createdAt"] = date("Y-m-d H:i:s");

                    $tdata["aff_id"] = $data["aff_id"];
                    $tdata["app_id"] = $data["app_id"];
                    $tdata["idfa"] = $data["idfa"];
                    $tdata["ip"] = $data["ip"];
                    $tdata["did"] = $data["did"];
                    $tdata["tid"] = $data["tid"];
                    $tdata["createdAt"] = $data["createdAt"];
                    $tdata["action_type"] = $data["action_type"];
                    $tdata["url"] = $data["url"];
                    $tdata["post"] = json_encode($data["post"]);
                    $tdata["request_log"] = json_encode($data["request_log"]);
                    $tdata["created_time"] = $data["created_time"];
                    $tdata["callback"] = isset($data["callback"])?$data["callback"]:null;
                    $tdata["transcation"] = isset($data["transcation"])?$data["transcation"]:null;
                    $attr_val[] = $tdata;
      
                }
                
                list($sql,$bind_param) = fetchInsertMoreSql("ofr_request_report",$attr_key,$attr_val);
                
                
                try{
                   
                    $r = db_execute($sql,"taskofr",$bind_param);
                    
                    if($r===false)return;
                     //清掉这部分内存
                    $r = $this->redis->ltrim($this->report_data_key.date("Y-m-d"),$start,-1);
                 
                    if($start){

                        echo "$start datas input completed.";

                        echo "\r\n\r\n";                    
                    }

                }catch(PDOException $e){

                    echo "failure:".$e->getMessage();

                }
                 
             }
                      
        } 

        //提取redis里面callback处理
        public function loopSaveCallbackData($length=10){
         
            $datas = $this->redis->lrange($this->task_callback_key.date("Y-m-d"),0,$length-1);
            
            $start = count($datas);

            if($start){
                
                $errors = 0;

                foreach ($datas as $k => $v) {
                    //处理这部分callback请求
                    $r = $this->_dealCallback($v);

                    if(0===$r)$errors++;
                }

                //清掉这部分内存
                $r = $this->redis->ltrim($this->task_callback_key.date("Y-m-d"),$start,-1);

                echo ($start - $errors) ." datas input completed.$errors datas failed.";

                echo "\r\n\r\n";                    
             
             }
                      
        } 


        public function callbackReq(){

            $this->request = $_REQUEST;
            //请求方式
            $this->request["type"] = strtolower($_SERVER['REQUEST_METHOD']);

            if(!isset($this->request["resid"])||empty($this->request["resid"]))

                xreturn("resid error.");
            
            $transcation = $this->request["resid"];

            $r = $this->_dealCallback($transcation,$isRedis=true);
            //优先查库 不存在 就进内存 否则直接处理掉
            switch ($r) {
                case -1:
                    xreturn("resid existed.");
                    break;
                
                case 0:
                    xreturn("resid accepted.");
                    break;

                case 1:
                    echo json_encode(array("success"=>1));
                    break;
            }
 
        }
        //isRedis 是否记录到redis / 或者是从redis读出 写入db
        public function _dealCallback($transcation){
             //有这个transcation
            $sql = "select count(0) from ofr_callback_log where transcation = ?";
            //echo $sql;
            $r = db_query_singal($sql,"taskofr",array($transcation));
            //已经有这个callback请求了
            
            if($r)return -1;

            //有这个transcation
            $sql = "select transcation,affiliate_id as aff_id,appid as app_id,callback,idfa,ip from ofr_request_report where transcation = ? and created_time between '".date("Y-m-d",strtotime("-1 day")) ."' and '".date("Y-m-d")."'";
            //echo $sql;
            $r = db_query_row($sql,"taskofr",array($transcation));

            //print_r($r);exit;
            //库里有回调
            if(isset($r["callback"])&&!empty($r["callback"])){
                
                $response = curl_req(urldecode($r["callback"]),null,"http_code"); 

                $r["http_code"] = $response["http_code"];
                //请求失败,记录到redis
                if($response["http_code"]!=200){
                    
                    $r["request_log"] = "";
                   
                }else {//返回200的结果

                    $r["request_log"] = $response["content"];   
                
                }
                //print_r($r);
                //只要不是存redis的写入数据
                list($sql,$bind_param) = fetchInsertMoreSql("ofr_callback_log",array(),$r,false,$db="taskofr");
                //echo $sql;print_r($bind_param);exit;
                $result = db_execute($sql,"taskofr",$bind_param);
            
                return 1;
            //没有回调
            }else{
                //不管是不是没有都存进去，初次存入redis，redis的数据也存，因为后面会清空这部分数据。
              
                    $this->insertCallbackData($transcation);

                    return 0;
   

            }

            return false;

        }
        


        //验证affiliate合法性
        public function tokenVaild($token,$aff_id){

            if($this->redis->get($this->affiliate_key.$token) == $aff_id)return 1;

            else {  
                //查过 存的0
                if($this->redis->get($this->affiliate_key.$token) == 0)return 0;

                $sql = "select id from ofr_affiliate where token=?";

                $id =  db_query_row($sql,"taskofr",array($token));

                if(count($id)<=0) {

                    $this->redis->set($this->affiliate_key.$token,0);    
                    //设置缓存10秒
                    $this->redis->expire($this->affiliate_key.$token,10);   
                    
                    return 0;
                }

                $this->redis->set($this->affiliate_key.$token,$id["id"]);                

                $id =  $this->redis->get($this->affiliate_key.$token);    

                if($id==$aff_id)return 1;

            }
            
            return 0;

        }


        //$storeid:应用号
        public function fetchIdfa($storeid){

            $sql = "select idfa from idfa where storeId = ?";

            $pdo = ini_pdo("laizhuan_task","mysql56.rdsmwvd8scqn9l4.rds.bj.baidubce.com:3306","laizhuan","laizhuan");

            $task_info =  db_query_col($sql,"laizhuan_task",array($storeid),$pdo);
            //print_r($task_info);
            //没有数据的情况，从task_log倒idfa到idfa表里（可能数据量过大需要分批导入，所以先计算总量）
            if(!count($task_info)){

                //从task_log倒idfa到idfa表里
                $sql = "insert into laizhuan_task.idfa(idfa,storeid) 
                        SELECT DISTINCT(idfa) idfa,storeid from laizhuan.task_log where storeid = ? and (task_end != '' or up_end != '')";

                $r = db_insert($sql,"laizhuan_task",array($storeid),$pdo);

                if($r===false)return false;

            }

            $sql = "SELECT count(DISTINCT(idfa)) idfa from idfa where storeid = ?";
            //先统计数量，数据太大无法存入数据库
            $count = db_query_singal($sql,"laizhuan_task",array($storeid),$pdo);

            //单次
            $unit = 10000;
            //循环次数
            $loop = $count/$unit;

            for($i=0;$i<=$loop;$i++){
                //提取前10000条数据
                 $sql = "SELECT DISTINCT(idfa) idfa from idfa where storeid = ? LIMIT ".$unit." OFFSET ".($i*$unit);
                // echo $sql;echo "<br>";
                 $list =  db_query_col($sql,"laizhuan_task",array($storeid),$pdo);
                 //print_r($list);exit;
                 
                 $pipe = $this->redis->multi(Redis::PIPELINE);   

                 foreach ($list as $key => $value) {
                    
                    $pipe->sAdd($this->idfa_key.$storeid,$value);
                    //$this->redis->sAdd($this->idfa_key.$storeid,$value);

                 }

                $pipe->exec();
                //添加过期时间
                $this->redis->expire($this->idfa_key.$storeid,$this->expire_cyc);

            }

            
            
        }

        //xxxxxxxxxxxxxx?idfa={idfa}&task_id={task_id}
        //自己做独立接口,进行排重
        public function uniqueInterface($param=array()){

            if(!count($param))$param = $_GET;
            //get传idfa
            if(!isset($param["app_id"])||!isset($param["idfa"]))

                echo json_encode(array("code"=>500,"msg"=>"param vaild."));
            
            $task_id = $param["app_id"];

            $idfa = $param["idfa"];

            $affiliate_id = $param["affiliate_id"];

            $task = $this->readTaskInfo($task_id,$affiliate_id);
            //接口名+appid 查有没有idfa记录集//$this->idfa_key.$storeid
            $infos = $this->redis->ssize($this->idfa_key.$task["storeid"]);

            //没有值，读库，存值
            if($infos==0){

                    $this->fetchIdfa($task["storeid"]);

            }
            //这个difa存在
            if($this->redis->scontains($this->idfa_key.$task["storeid"], $idfa))return false;//echo json_encode(array("status"=>1));

            else {
                //是否新跑的里面有，新跑的idfa存在了
                if($this->redis->scontains($this->idfa_new_key.$task["storeid"], $idfa)){

                    //echo json_encode(array("status"=>1));
                    return false;
                }else{//排重成功了,记录下来
                    
                    $this->redis->sAdd($this->idfa_new_key.$task["storeid"],$idfa);

                    //echo json_encode(array("status"=>0));
                    return true;
                }
            }
            

        }


        public function clearRedisData(){

                $idfa_keys = $this->redis->keys($this->idfa_new_key.'*');
                //print_r($idfa_keys);exit;
                if($idfa_keys&&is_array($idfa_keys)){

                    foreach ($idfa_keys as $key) {


                        //从key名称获取storeid
                        list($t,$storeid) = explode($this->idfa_new_key,$key);
                        //取得数据
                        $datas = $this->redis->sMembers($key);

                        //idfa list
                        $infos = $this->redis->ssize($this->idfa_key.$storeid);

                        //$sql = "insert into laizhuan_task.idfa(storeid,idfa)values";
                        
                        $attr_key = array("storeid","idfa");

                        $attr_val = array();

                        foreach ($datas as $value) {
                            
                            //$sql .= "(".$storeid.",'".$value."'),";
                            $attr_val[] = array($storeid,$value);
                            //排重加入到原来idfa list中
                            if($infos){
                                $this->redis->sAdd($this->idfa_key.$storeid,$value);
                            }
                            
                        }
                        //有数据更新后，重新添加过期时间
                        $this->redis->expire($this->idfa_key.$storeid,$this->expire_cyc);

                        //$sql = trim($sql,",");
                        list($sql,$bind_param) = fetchInsertMoreSql("idfa",$attr_key,$attr_val);
                        //echo $sql;
                        //echo $key;
                        $pdo = ini_pdo("laizhuan_task","mysql56.rdsmwvd8scqn9l4.rds.bj.baidubce.com:3306","laizhuan","laizhuan");

                        $r = db_insert($sql,"laizhuan_task",$bind_param,$pdo);//exit;
                        //清除该内存
                        //if($r!==false)$this->redis->del($key);
                    }//exit;
                    
                }
                //exit;
                $this->redis->delete($this->redis->keys($this->task_info_key.'*'));

                //$this->redis->delete($this->redis->keys($this->idfa_key.'*'));

                $this->redis->delete($this->redis->keys($this->affiliate_key.'*'));

                //$this->redis->expire($this->task_info_key.$task_id,$this->expire_cyc);
        }


    }

       
       // $RedisCache = new RedisCache();
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