<?php



class Record extends RecordModule {
	//单次处理数量
	protected $loop = 5000;

	protected $pdo;

	public function __construct(){
		   //定义了日期
        parent::__construct();

        $this->pdo = new RecordPdo();
	}


	public function index(){

		//$this->dailyReport();
	}


    //点击记录,分享记录，搜索记录。
    public function userActionRecord(){

       // $m = load_module("goods");;
        
        $r = new RedisCache();

        $r->index();
    }

//---------------dailyReport-----------------//
	
	//每日点击&购买数据汇集 —— 昨日数据
    public function dailyReportRecord(){

        $rt = $this->pdo->insertDailyReport();

        if($rt!==false)//echo date("Y-m-d H:i:s").":transcation daily purchase success.\r\n";
            
            echo 1;
        else {
            //echo date("Y-m-d H:i:s").":transcation daily purchase fail.\r\n";
            echo 0;            
        }


    }


     

    //
    //购物消息队列后的补全gw_order表的数据（外部调用）

    public function updateOrderInfo($order_list=array()){

        //$order_list = array(32344443,345345345,2342343,7145541093113222);
        
        if(count($order_list)==0)return ssreturn(0,'参数格式有误.',2) ;

        $num_iid = $this->pdo->fetchOrderInfoGoods($order_list);
        //print_r($num_iid);
        /*
        $sql = "replace into ".TALBE_PRE."order (order_id,uid,type,status,num_iid,coupon_id,title,pict_url,item_url,price,rating,seller_id,store_type,created_date,deal_price,category,category_id,favorite_id,favorite,source)select order_id,uid,type,1,b.num_iid,a.coupon_id,a.title,a.pict_url,a.item_url,a.price,a.rating,a.seller_id,a.store_type,a.created_date,a.deal_price,a.category,a.category_id,a.favorite_id,a.favorite,a.source from ".TALBE_PRE."goods_online a right join ".TALBE_PRE."order b where a.num_iid in(".implode(",",$num_iid).") and b.num_iid in(".implode(",",$num_iid).")";

        $r = db_execute($sql,$this->db,array(),$this->pdo);*/
        $r = $this->pdo->updateOrderInfo($num_iid);

         if($r===false)
            return ssreturn(0,'订单补全执行失败.',2,1) ;
        else
           return ssreturn($r,'操作成功.',1,1) ;
            

        

    }

    public function purchaseRecord($order_id_list=array(),$order_status=2){
        
        if(is_array($order_id_list)&&count($order_id_list)){

            $r = $this->pdo->insertPurchaseRecord($order_id_list,$order_status);

            if($r===false)
                return ssreturn(0,'购买记录执行失败.',2) ;
            else{
                return ssreturn($r,'操作成功.',1) ;
            } 
            //return $r;

        }

        return ssreturn(0,'参数格式有误.',2) ;
    }



}