<?php

header("Content-type: text/html; charset=utf-8");                
/*
商品类
 */
class FilterConfigController extends Controller{
	//保留之前的商品数据
    public $limit_old_goods = 1000;

    public $date;

    public $pdo;

    public $db;

     public $isDebug = 0;
    
    public function __construct(){

        $this->pdo = $this->isDebug?jpLaizhuanCon("shopping"):shoppingCon();

        $this->db = $this->isDebug?"shopping":"huitao";

        $this->date = isset($_GET["date"])&&!empty($_GET["date"])?$_GET["date"]:date("Y-m-d");   
        //报表间隔计算
        $this->pastDate = 30;
       

    }

    public function _create_filter_rule(){

        $sql = "select * from gw_filter_config_detail where strategy_id = 1";

        $rt = db_query($sql,"gw",array(),$this->pdo);
        //print_r($rt);
        $filter_key = "";

        foreach ($rt as $key => $value) {

            $type = $value["type"];
           
            $rating = $value["rating"];
            //0 - 点击比 1 - 购买比 2 - top 3 - 佣金比
            switch ($type) {
                
                case 0:
                    $filter_key .= "$rating * click_rate+";
                    
                break;

                case 1:
                    $filter_key .= "$rating * purchases_rate+";
                break;

                case 2:
                    $filter_key .= "$rating * top+";
                break;
                
                case 3:
                    $filter_key .= "$rating * (100 - rating)/100)+";
                break;

            }

        }

        if($filter_key)return "(".trim($filter_key,"+")."* 100 ";

        return "";

    }

    public function fetchSum(){

        $sql = "select sum(click) clicks,sum(purchase) purchases from gw_goods_daily_report
                    where report_date BETWEEN '".date("Y-m-d",strtotime($this->date) - $this->pastDate*3600*24)."' and '".$this->date."' and order_status = 2";
       // echo $sql;
        $sum = db_query_row($sql,"gw",array(),$this->pdo);

        return $sum;


    }

    //把排序外的商品下架 status=0 &　
    public function sort(){

        $sum = $this->fetchSum();

        $sql_list = array();

        //取status=1，时间是今日之前(不是今天上架的商品)，根据排序方式 进行排序 取出商品id num_idd ，把部分商品下架 status = 0
        //
        //
       // $filter_rule_sql = $this->_create_filter_rule();

        //if($filter_rule_sql)

        //按照份数排序 去掉过期的优惠券的
        $temp_sql = "select num_iid from (     

        select a.num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,rating,seller_id,seller_name, store_name,store_type,top,created_date,
        taobao_cid,gw_id,sum,num,val,limited,reduce,discount,deal_price, start_time,end_time,url,coupon_url,".$this->_create_filter_rule()." filter_rule from 

        (SELECT * from gw_goods_online where status = 1 and created_date <'".$this->date."')a LEFT JOIN 

        (select sum(click)/".$sum["clicks"]." click_rate,sum(purchase)/".$sum["purchases"]." purchases_rate,num_iid from gw_goods_daily_report 

            where report_date BETWEEN '".date("Y-m-d",strtotime($this->date) - $this->pastDate*3600*24)."' and '".$this->date."' 

         and order_status = 2 GROUP BY num_iid)b on a.num_iid = b.num_iid 

            where (a.start_time <= '".$this->date."' and a.end_time >= '".$this->date."') 

                ORDER BY filter_rule desc limit ".$this->limit_old_goods.")a";
        
        $num_iid_list = db_query_col($temp_sql,"gw",array(),$this->pdo);
        //print_r($num_iid_list);exit;
            //echo $temp_sql;exit;
        if(count($num_iid_list)){
            
            $this->refresh_sort($num_iid_list);

            //把排序外的商品下架 status=0 &　之前的 当天的不要
            $sql_list[]  = "UPDATE gw_goods_online SET status = 0 where num_iid not in (" . implode(",",$num_iid_list) . ") and status = 1 and created_date <'".$this->date."'";

            //把重复的数据，之前的更改了
            $sql = "select num_iid from gw_goods_online where status = 1 GROUP BY num_iid having count(0) > 1";
            //$sql = "select num_iid from gw_goods_online  where num_iid in (" . implode(",",$num_iid_list) . ")  GROUP BY num_iid having count(0) > 1";
                //echo $sql;
                $repeat_num_iid = db_query_col($sql,$this->db,array(),$this->pdo);

            if(!count($repeat_num_iid))$repeat_num_iid=array('0');

            $sql_list[]  = "UPDATE gw_goods_online SET status = 0 where num_iid in (" . implode(",",$repeat_num_iid) . ") and status = 1 and created_date <'".$this->date."'";
       
            //print_r($sql_list);exit();

            $rt = db_transaction($this->pdo, $sql_list);

            if($rt)
                return ssreturn(1,date("Y-m-d H:i:s").":daily purchase success.",1,1);
            //echo date("Y-m-d H:i:s").":transcation daily purchase success.\r\n";

            else {
                return ssreturn(0,date("Y-m-d H:i:s").":daily purchase fail.",1,1);
                //echo date("Y-m-d H:i:s").":transcation daily purchase fail.\r\n";                exit;
            }
         }else return ssreturn(0,date("Y-m-d H:i:s").":no past data.",1,1);//echo date("Y-m-d H:i:s").":no past data.\r\n";


    }
    //更新排序表
    public function refresh_sort($num_iid_list){

        $sql_list[] = "delete from gw_goods_sort where type = 1";

        $insert_sql = "insert into gw_goods_sort(num_iid,sort,type)values";  

        $insert_val = "";

        foreach ($num_iid_list as $key => $value) {
            
            $insert_val .= "(".$value.",".($key+1).",1),";

        }

        $temp_sql = $insert_sql . trim($insert_val,",");
        //echo $temp_sql;exit;
        $sql_list[] = $temp_sql;

        $rt = db_transaction($this->pdo, $sql_list);

        if($rt)
            return ssreturn(0,date("Y-m-d H:i:s").":transcation refresh sort success.",1,1);
        //echo date("Y-m-d H:i:s").":transcation refresh sort success.\r\n";

        else {
           return ssreturn(0,date("Y-m-d H:i:s").":transcation refresh sort fail.",1,1);
           // echo date("Y-m-d H:i:s").":transcation refresh sort fail.\r\n";
           // exit;
        }

    }
    
}


