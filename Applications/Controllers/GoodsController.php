<?php

header("Content-type: text/html; charset=utf-8");
/*
商品类
 */
class GoodsController extends Controller{

    //过滤条件：
    //单个类目的条数:0-没限制
    public $category_limit = 0;
    //佣金比 0代表没限制 []代表区间 可以为单一
    public $rating_limit = array(10);
    //价格
    //public $price_limit = array(0,300);
    //折扣力度（越小力度越大）,所以从0到某个范围,百分比表示
    public $discount_limit = array(0,80);
    //成交价
    public $deal_price_limit = array(0.01);

    public $coupon_num_limit = array(1000);
    //佣金金额 > 1块
    public $rating_price_limit = 1;

    ////按照成交价排序,价格太小 改成折扣率
    //public $sort = "deal_price";
     public $sort = "top desc,discount asc";
    //取出3000条
    //public $query_limit = 3000;

    //取2000条存入sort表
    public $limit = 3000;
    //执行5次
    public $insert_num = 10;
    //一次2000条
    public $insert_limit = 2000;

    public $filterConfig;

    public $pdo;

    public $db;

    public $isDebug = 0;

    public function __construct(){

       // $this->pdo = ini_pdo("gw","localhost:3306","root","root");

       // $this->db = "gw";

        $this->pdo = $this->isDebug?jpLaizhuanCon("shopping"):shoppingCon();

        $this->db = $this->isDebug?"shopping":"huitao";

        $this->date = isset($_GET["date"])&&!empty($_GET["date"])?$_GET["date"]:date("Y-m-d");

        $this->filterConfig = new FilterConfigController;

    }

    public function index(){
        //记录仓库表,今天的数据
        //$this->createWareTable();
        //筛选规则，筛选,inputGoodsTable内部使用
        //$filter_sql = $this->filterRuleFirst();

        //!!*
        //一次导入，去掉了中间的仓库表 直接导入1000条数据 按折扣率排序
        return $this->inputGoodsTable();

    }
    //热销商品 
    public function hot_sell_goods(){
        $sql = "select num_iid,rating,price,top from gw_goods where created_date = '2017-01-11'";
        $rt = db_query($sql,$this->db,array(),$this->pdo);
        //print_r($rt);
    }

      //更新排序表
    public function refresh_sort($num_iid_list){
        $this->filterConfig->refresh_sort($num_iid_list,2);
    }
    //记录仓库表
    //新增当日所有数据
    public function createWareTable(){

            $date =  $this->date;

            $sql = "select count(0) from gw_goods where created_date = '".$date."'";

            $count = db_query_singal($sql,$this->db,array(),$this->pdo);

            if($count==0){
                echo date("Y-m-d")."no data"."\r\n";
                exit;
            }

            $sql_list = array();

            $sql_list[] = "DELETE FROM gw_goods_ware WHERE created_date = '".$date."'";


            $insert_sql = "insert into gw_goods_ware(

                        num_iid,coupon_id,created_date,title,pict_url,item_url,category,promotion_url,price,volume,rating,

                seller_id,seller_name,store_name,store_type,top,taobao_cid,gw_id,sum,num,val,

                    limited,reduce,start_time,end_time,url,coupon_url,discount,deal_price)";


            $sql =  "SELECT a.num_iid,a.coupon_id,a.created_date,title,pict_url,item_url,category,promotion_url,price,volume,rating,

                seller_id,seller_name,store_name,store_type,top,taobao_cid,gw_id,sum,num,val,

                    limited,reduce,start_time,end_time,url,coupon_url,IF(price>reduce and price>=limited,(price-reduce)/price*100,0) discount,IF(price>reduce and price>=limited,price-reduce,0) deal_price from

                (SELECT a.*,b.id gw_id,b.taobao_cid,b.name from

                    (
                        select * from gw_goods where created_date = '".$date."'

                    )a LEFT JOIN gw_category b on a.category = b.taobao_category_name

                )a left JOIN (

                    select * from gw_goods_coupon where created_date = '".$date."'

                )b on a.num_iid = b.num_iid and a.coupon_id = b.coupon_id";
            //echo $sql;exit;
            $sql_list[] = $insert_sql . $sql;
            //print_r($sql_list);exit;
            $rt =  db_transaction($this->pdo, $sql_list);

               // echo $sql;

            if($rt)echo date("Y-m-d H:i:s").":transcation ware $count data success.\r\n";

            else {
                echo date("Y-m-d H:i:s").":transcation ware fail.\r\n";
                exit;
            }
    }
    //筛选规则1: 佣金比>10 && 折扣力度小于8折  && 优惠券数量大于1000
    //1.佣金比
    //2.成交价格（必须大于0，0-代表 价格低于优惠券门槛
    //3.折扣比（价格-优惠）/ 价格
    //4.去重了相同的商品
    //优惠券必须达到
    public function filterRuleFirst(){

        /*$sql = "select num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,rating,seller_id,seller_name,
                store_name,store_type,top,created_date,taobao_cid,gw_id,sum,num,val,limited,reduce,discount,deal_price,
                start_time,end_time,url,coupon_url from gw_goods_ware where created_date = '".$this->date."'";
        */

        $con_sql = "SELECT a.num_iid,a.coupon_id,a.created_date,title,pict_url,item_url,category,promotion_url,price,volume,rating,

                seller_id,seller_name,store_name,store_type,top,taobao_cid,gw_id,gw_name,gw_pid,sum,num,val,

                    limited,reduce,start_time,end_time,url,coupon_url,IF(price>reduce and price>=limited,(price-reduce)/price*100,0) discount,IF(price>reduce and price>=limited,price-reduce,0) deal_price from

                (SELECT a.*,b.id gw_id,b.taobao_cid,b.name gw_name,b.pid gw_pid from

                    (
                        select * from gw_goods where created_date = '".$this->date."'

                    )a LEFT JOIN gw_category b on a.category = b.taobao_category_name where pid > 0

                )a left JOIN (

                    select * from gw_goods_coupon where created_date = '".$this->date."'

                )b on a.num_iid = b.num_iid and a.coupon_id = b.coupon_id";




        $sql = "select num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,rating,seller_id,seller_name,
                store_name,store_type,top,created_date,taobao_cid,gw_id,gw_name,gw_pid,sum,num,val,limited,reduce,discount,deal_price,
                start_time,end_time,url,coupon_url from "."(".$con_sql.")t"." where created_date = '".$this->date."'";


        $condition = "";
        //1.佣金比
        $condition .= $this->ratingFilter();
        //2.成交价格 至少大于0 =0代表 优惠券无法使用
        $condition .=  $this->dealPriceFilter();
        //3.优惠比例（价格-优惠力度）/ 价格
        $condition .=  $this->discountLimitFilter();
         //!!*因为有限制会过滤掉一部分数据，所以or top > 0
        //$condition = trim($condition ,")") . " or top > 0)";
        //4.优惠券数量
        $condition .=  $this->couponNumLimitFilter();
        //5.优惠券日期限制
        $condition .=  $this->couponDateLimitFilter($this->date);
        //!!*因为有限制会过滤掉一部分数据，所以or top > 0
        //!开始时间可能是没有的，所以没加，但是有少数 还未开始的活动
        $condition .= $this->topSoldFilter($this->date);

        //去重商品
        $group = "GROUP BY num_iid";
        //按照成交价排序
        $sort = " order by " .$this->sort;

        $limit = " limit ".$this->limit;

        $sql = $sql . $condition . $group . $sort . $limit;

        return $sql;

    }

    //上架商品表
    public function inputGoodsTable(){

           //$date =  $this->date;

            $delete_sql = "DELETE FROM gw_goods_online WHERE created_date = '".$this->date."'";

            $filter_sql = $this->filterRuleFirst();
            //echo $filter_sql;exit;
           // if(!$filter_sql)
            $insert_sql = "insert into gw_goods_online(
                num_iid,coupon_id,title,pict_url,item_url,category,promotion_url,price,volume,rating,seller_id,seller_name,
                store_name,store_type,top,created_date,taobao_cid,gw_id,gw_name,gw_pid,sum,num,val,limited,reduce,discount,deal_price,
                start_time,end_time,url,coupon_url)".$filter_sql;
           //echo $insert_sql;exit;

            $rt =  db_transaction($this->pdo,array($delete_sql,$insert_sql));

            if($rt){
                /*
                 //!!去掉已经存在的相同的商品,这些商品不再次作为新品展示
                $sql = "select num_iid from gw_goods_online WHERE created_date = '".$this->date."' order by discount";
               // echo $sql;
                $num_iid = db_query_col($sql,$this->db,array(),$this->pdo);
                //print_r($num_iid);exit;
                if(count($num_iid)==0){ echo "no data";return;}
                                
                //$sql_list[] = "DELETE FROM  gw_goods_online where status = 1 and created_date <'".$this->date."' and num_iid in(".implode(",",$num_iid).")";
                $sql_list[] = "UPDATE gw_goods_online SET status = 0 where status = 1 and created_date <'".$this->date."' and num_iid in(".implode(",",$num_iid).")";
                */
               
                $sql = "select distinct(num_iid) from gw_goods_online WHERE created_date = '".$this->date."'";

                $all_num_iid = db_query_col($sql,$this->db,array(),$this->pdo);
                 //!!*去掉已经存在的相同的商品,这些商品不再次作为新品展示
                 //!取出了已经在货架的商品，
                $sql = "select num_iid from gw_goods_online where status = 1 GROUP BY num_iid having count(0) > 1";
                //$sql = "select num_iid from gw_goods_online where num_iid in (" . implode(",",$all_num_iid) . ") GROUP BY num_iid having count(0) > 1";
                //$sql = "select distinct(num_iid) from gw_goods_online where GROUP BY num_iid having count(0) > 1";
               // echo $sql;
                $repeat_num_iid = db_query_col($sql,$this->db,array(),$this->pdo);

                //print_r($sql_list);exit;
                //echo $sql;exit;
                //$rt = db_execute($sql,$this->db,array(),shoppingCon());

                $sql_list[] = "delete from gw_goods_sort where type = 2";

                $insert_sql = "insert into gw_goods_sort(num_iid,sort,type)values";

                $insert_val = "";

                foreach ($all_num_iid as $key => $value) {

                    if($key >= $this->insert_limit)break;
                    //旧商品跳过
                    if(in_array($value,$repeat_num_iid)){
                        continue;
                    }

                    $insert_val .= "(".$value.",".($key+1001).",2),";

                }
                $temp_sql = $insert_sql . trim($insert_val,",");

                $sql_list[] = $temp_sql;
               // print_r($sql_list);exit;
                $rt = db_transaction($this->pdo, $sql_list);
               
                if($rt)//return true;//echo date("Y-m-d H:i:s").":transcation goods success.\r\n";
                    $r = ssreturn(1,date("Y-m-d H:i:s").":transcation goods success.",1,1);
                else //return false;//echo date("Y-m-d H:i:s").":transcation goods fail.\r\n";
                    $r = ssreturn(0,date("Y-m-d H:i:s").":transcation goods fail.",1,1);


            }
            else {
               
                if($rt===0)//echo date("Y-m-d H:i:s").":transcation goods success.\r\n";
                    $r = ssreturn(1,date("Y-m-d H:i:s").":transcation goods success.",1,1);

                else //echo date("Y-m-d H:i:s").":transcation goods fail.\r\n";
                    $r = ssreturn(0,date("Y-m-d H:i:s").":transcation goods fail.",1,1);
            }
            
           return $r;

    }




    protected function _unitFilter($filter_arr,$attr,$alies=''){

        $str = "";

        $attr = $alies ? $alies . ".$attr" : $attr;

        if(is_array($filter_arr)&&count($filter_arr)){

            $str = $attr . " >= " . $filter_arr[0];

            if(count($filter_arr)>1){

                $str .= " and ". $attr . " <= " . $filter_arr[1];
            }
        }

        if($str)return " and (". $str . ")";

        return $str;

    }
    //佣金比
    protected function ratingFilter($alies=""){

        return $this->_unitFilter($this->rating_limit,"rating",$alies);

    }

    //成交价
    protected function dealPriceFilter($alies=""){

        return $this->_unitFilter($this->deal_price_limit,"deal_price",$alies);

    }

    //折扣
    protected function discountLimitFilter($alies=""){

        return $this->_unitFilter($this->discount_limit,"discount",$alies);

    }
    //优惠券数量
    protected function couponNumLimitFilter($alies=""){

        return $this->_unitFilter($this->coupon_num_limit,"num",$alies);

    }
    //优惠券日期有效期
    protected function couponDateLimitFilter($date,$alies=""){

        return " and (start_time <= '".$date."' and end_time >= '".$date."')";

    }

    //!!*因为有限制会过滤掉一部分数据，所以or top > 0
    //!开始时间可能是没有的，所以没加，但是有少数 还未开始的活动
    protected function topSoldFilter($date,$alies=""){

       return " or (top > 0 and (rating * deal_price >= ".$this->rating_price_limit." ) and end_time >= '".$date."') ";
    }


    //下架规则
    public function downGoods(){
        /*
        1.优惠券剩余量消耗完毕
        2.优惠券有效期过期
        */

    }

    //过滤规则
    public function filterGoods(){

    }

}


