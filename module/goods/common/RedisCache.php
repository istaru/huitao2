<?php

class RedisCache extends GoodsModule{

        public $db;

        public $pdo;

        public $click_data_key = "click_data_";

        public $share_data_key = "share_data_";

        public $purchase_date_key = "purchase_data_";

        public $search_data_key = "search_data_";

        public $isDebug = 1;

        //默认过期时间 7天
        public $expire_cyc;

        public $date;

        public $redis;

        public $table_pre = "ngw_";

        function __construct(){
           // echo 1;
            //$this->pdo = jpLaizhuanCon("shopping");

            //$this->db = "jpItem";
            //
            if(substr(php_sapi_name(), 0, 3) == 'cli'){

                $arr = getopt('d:');

                $this->date = isset($arr['d']) ? $arr['d'] : date("Y-m-d");

            }else{

                $this->date = isset($_GET["date"]) ? $_GET["date"] : date("Y-m-d");
            }

            $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

            $this->db = $this->isDebug?"shopping_new":"huitao";
            // echo 2;
            $this->expire_cyc = 3600 * 24 * 7;

            $this->redis = $this->connectRedis();

            if(!$this->redis){

                system("/usr/bin/redis-server");

                $this->redis = $this->connectRedis();
            
            }//redis挂了
            if(!$this->redis)echo "redis down...";

            //print_r($this->redis);
        }

        function index(){


            $n=3;
            
            for($i=0;$i<$n;$i++){
                //点击行为记录
                $click_data = array("num_iid"=>23423443,"uid"=>"svldsjf".$i,"click"=>$i,"type"=>2,"report_date"=>$this->date);
                echo $this->insertUidClickData($click_data);
                echo "<br>";
                //搜索行为记录
                $search_data = array("num_iid"=>23423443,"uid"=>"svldsjf".$i,"search_content"=>'vsdf'.$i,"type"=>2,"report_date"=>$this->date);
                echo $this->insertUidSearchData($search_data);
                echo "<br>";
                //分享行为记录
                $share_data = array("num_iid"=>23423443,"uid"=>"svldsjf".$i,"type"=>2,"share_type"=>2,"report_date"=>$this->date);
                echo $this->insertUidShareData($share_data);
                echo "<br>";
            }/*
            print_r($this->redis->lrange($this->click_data_key.$this->date,0,-1));
            */
            //取出并存入点击数据
            $this->readUidClickInfo(2);
            //取出并存入搜索数据
             $this->readUidSearchInfo(2);
             //从内存取出并出入分享数据
              $this->readUidShareInfo(2);
        }

        public function connectRedis(){

            if($this->redis)return $this->redis;

            $this->redis = new redis(); 
            
            $r = $this->redis->connect('127.0.0.1', 6379); 

            if(!$r)return null;

            return $this->redis;

        }

        //插入点击数据
        public function insertUidClickData($data){

            //将用户数据存入LIst的末尾。
            //<report_date,uid,num_iid,click,createdAt>
            return $this->redis->rpush($this->click_data_key.$this->date,json_encode($data));
        }

        //记录用户点击信息
        //uid:用户id
        //item_id:商品id
        //action:动作0-click 1-purchase
        public function readUidClickInfo($length=2000){

             return $this->readRedisInfo($length,$this->click_data_key,$this->table_pre."click_log");
        }

        //插入分享数据
        public function insertUidShareData($data){

            return $this->redis->rpush($this->share_data_key.$this->date,json_encode($data));
        }

         //记录用户分享数据
        public function readUidShareInfo($length=2000){

             return $this->readRedisInfo($length,$this->share_data_key,$this->table_pre."share_log");
        }


        //插入搜索数据
        public function insertUidSearchData($data){

            return $this->redis->rpush($this->search_data_key.$this->date,json_encode($data));
        }

        //记录用户搜索信息
        public function readUidSearchInfo($length=2000){

             return $this->readRedisInfo($length,$this->search_data_key,$this->table_pre."search_log");
        }

        //存入redis数据
        //param : 每次取出的数据
        //date
        public function readRedisInfo($length=2000,$key,$insert_table){
            
             $date =  $this->date;

             $redis_key = $key.$date;
             //总数据数
             $total = $this->redis->lsize($redis_key);
             //2000一次处理，循环多少次
             $loop = ceil( $total / $length );
             //echo "loop:".$loop."<br>.";
             for($index=0;$index<$loop;$index++){
                   
                 $datas = $this->redis->lrange($redis_key,0,$length-1);
                 
                 $start = count($datas);
                 
                 if($start){

                    $deal_data = array();
                    
                    foreach ($datas as $k => $v) {
                        //$v是空值
                        if($v!==null)
                            //解码存入
                            $deal_data[] = json_decode($v,true);

                    }   
                    //print_r($deal_data);exit;
                    //批量插入，不是二维
                    if(!is_array($deal_data[0])){
                        //清掉这部分内存,都是null 不是数组数据
                        $r = $this->redis->ltrim($redis_key,$start,-1);
                        continue;
                    };
                    //批量插入
                    list($sql,$insert_data) = fetchInsertMoreSql($insert_table,array_keys($deal_data[0]),$deal_data,false,$this->pdo);               
                    //echo $sql;print_r($insert_data);//exit;
                    $rt = db_execute($sql,$this->db,$insert_data,$this->pdo); 
                    //echo $rt;
                    if($rt!==false){     
                        //清掉这部分内存
                        $r = $this->redis->ltrim($redis_key,$start,-1);
                       
                        //if($r<=0)return $r;//xreturn("clear redis ".$this->click_data_key.date("Y-m-d")." unvalid.");

                        //else return $r;
                    }
                }
                /*else {
                    echo "no data.";
                    return 0;
                }*/
            }
            echo 1;
            return 1;
        }

        

        public function insertUidPurchaseData($data){
            //将用户数据存入LIst的末尾。
            //<report_date,uid,num_iid,click,createdAt>
            $this->redis->rpush($this->purchase_date_key.date("Y-m-d"),json_encode($data));
        }

        //读写reids
        public function redis($key,$val=""){

            if($val)$this->redis->set($key,$val);

            else return $this->redis->get($key);

        }


    }