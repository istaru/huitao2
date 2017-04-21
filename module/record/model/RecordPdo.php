<?php



class RecordPdo extends RecordModule {
	//单次处理数量
	protected $loop = 5000;

	protected $pdo;

	public function __construct(){
		   //定义了日期
        parent::__construct();

	}


	public function index(){

	}

	//当日的商品点击&购买情况
	public function insertDailyReport(){

		$sql_list[] = "delete from ".TALBE_PRE."goods_daily_report where report_date = '".$this->date."'  and (click = 0 or purchase = 0)";
		//会有订单匹配不到num_iid的情况
		$sql_list[] = "insert into ".TALBE_PRE."goods_daily_report(report_date,num_iid,click,fee,benifit,amount,source_type,type,order_status,purchase)
			            select report_date,num_iid,0,sum(fee) fee,sum(benifit) benifit,sum(amount) amount,source_type,type,order_status,count(num_iid) purchase from ".TALBE_PRE."shopping_log where order_status in (".implode(",",PUR_ORDER_STS).") and report_date = '".$this->date."'
			            GROUP BY num_iid,order_status,type,source_type";

	    $sql_list[] = "insert into ".TALBE_PRE."goods_daily_report(report_date,num_iid,click,type,order_status,purchase)
		            	select report_date,num_iid,sum(click) click,type,2,0 from ".TALBE_PRE."click_log
		              	where report_date = '".$this->date."' GROUP BY num_iid,type";
		//print_r($sql_list);
	    $r = db_transaction($this->pdo,$sql_list);
        
        return $r;

	}


	public function fetchOrderInfoGoods($order_list){

		$sql = "select distinct(open_id) num_iid from ".TALBE_PRE."order a 
    
            left join ".TALBE_PRE."order_status b on a.order_id = b.order_id

        where a.order_id in (".implode(",",$order_list).") and a.status = 0 and b.order_id in (".implode(",",$order_list).") and open_id > 0 and num_iid > 0";
        //echo $sql;exit;

        $result = db_query_col($sql,$this->db,array(),$this->pdo);

        return $result;  

	}
	//补全订单信息
	public function updateOrderInfo($num_iid){

		if(!is_array($num_iid)||!count($num_iid))return false;

		  $sql = "replace into ".TALBE_PRE."order (order_id,uid,type,status,num_iid,coupon_id,title,pict_url,item_url,price,rating,seller_id,store_type,created_date,deal_price,category,category_id,favorite_id,favorite,source)select order_id,uid,type,1,b.num_iid,a.coupon_id,a.title,a.pict_url,a.item_url,a.price,a.rating,a.seller_id,a.store_type,a.created_date,a.deal_price,a.category,a.category_id,a.favorite_id,a.favorite,a.source from ".TALBE_PRE."goods_online a right join ".TALBE_PRE."order b on a.num_iid = b.num_iid where a.num_iid in(".implode(",",$num_iid).") and b.num_iid in(".implode(",",$num_iid).") GROUP BY num_iid";
		  //!!*GROUP BY num_iid 是以免有多种来源或者多个分类的同名商品，被反复添加订单数据
		 //echo $sql;
        $r = db_execute($sql,$this->db,array(),$this->pdo);


        return $r;
	}
	
	
	public function insertPurchaseRecord($order_id_list,$order_status){

		if(!is_array($order_id_list)||!count($order_id_list))return false;
		
        $sql = "REPLACE INTO ".TALBE_PRE."shopping_log ( STATUS, uid, report_date, num_iid, fee, benifit, amount, rating, type, order_id, order_status, taobao_nick ) SELECT 1, b.uid, a.created_date, a.num_iid, paid_fee * auction_amount fee, paid_fee * auction_amount * b.rating / 100 benifit, auction_amount, rating, a.type, a.order_id, a. STATUS, seller_nick FROM ".TALBE_PRE."order_status a JOIN ".TALBE_PRE."order b ON a.order_id = b.order_id WHERE a.order_id IN (".implode(",",$order_id_list).") AND a. STATUS = ".$order_status;
		//echo $sql;//exit;
        $r = db_execute($sql,$this->db,array(),$this->pdo);
        //echo $r;
         return $r;

	}
}
