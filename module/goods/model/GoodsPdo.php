<?php

class GoodsPdo extends GoodsModule{

    public $pdo;

    public $db;
   
    public $table_pre = "gw_";

    public function __construct($isDebug){

        parent::__construct();

        $this->isDebug = $isDebug;

        $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

        $this->db = $this->isDebug?"shopping_new":"huitao";



        }
    }

    //选品库
class FavoriteGoodsPdo extends GoodsPdo{

     public $adzone_id;

     public function __construct($isDebug,$adzone_id){

        parent::__construct($isDebug);

        $this->$adzone_id = $adzone_id;

     }
     //插入非失效信息到商品库表
    //@$category_info : $key => $value 选品库类型
    public function InsertEffortsToGoods($data,$favorite_info,$category_info){

        list($category_id,$category) = $category_info;
        
        list($favorite_id,$favorite) = $favorite_info;
    
        $sql = "replace into ".$this->table_pre."goods (source,num_iid,title,pict_url,small_images,item_url,category,category_id,favorite,favorite_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time)values";
        
        $values = array();
       
        $bindParam = array();
        
        $c = '';

        //失效的商品信息
        $invalid_goods_list = array();
        //有效的商品信息，清空老数据用
        $valid_goods_list = array();
       
        foreach ($data as $k => $v) {
            //失效商品,记录商品号
            //&&1==2
            if($v["status"] == 0) {

                 $invalid_goods_list[] = $v["num_iid"];

                 continue;

            }
            //
            if(!setVaildParam($v,"num_iid"))continue;
            //有效的商品信息，清空老数据用
            else $valid_goods_list[] = $v["num_iid"];

            $c .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),";
            
            $t = array(1,
                    setVaildParam($v,"num_iid"),
                    setVaildParam($v,'title',''),
                    setVaildParam($v,'pict_url',''),
                    json_encode(setVaildParam($v,'small_images','')),
                    setVaildParam($v,'item_url',''),
                    $category,$category_id,
                    $favorite,$favorite_id,
                    setVaildParam($v,"reserve_price"),
                    setVaildParam($v,"volume"),
                    setVaildParam($v,"tk_rate"),
                    setVaildParam($v,"seller_id"),
                    setVaildParam($v,"shop_title",''),
                    setVaildParam($v,"user_type"),
                    (setVaildParam($v,"zk_final_price")&&setVaildParam($v,"reserve_price")? number_format($v["zk_final_price"]/$v["reserve_price"]*100,2) : 0),
                    setVaildParam($v,"zk_final_price"),
                    $this->date,
                    setVaildParam($v,"event_start_time"),
                    setVaildParam($v,"event_end_time")
                );

            //print_r($t);exit;

            $bindParam = array_merge($bindParam,$t);
            # code...
            
        }

        $sql = $sql.trim($c,",");

        $r = db_insert($sql,$this->db,$bindParam,$this->pdo);

        $invalid_goods_list = count($invalid_goods_list) ? implode(",",$invalid_goods_list)."," : '';

        if($r<=0)return array($invalid_goods_list,0);
        
        return array($invalid_goods_list,count($valid_goods_list));
        
    }

    //取出某个分类下现在上架的所有商品
    public function fetchOnlineNumIdByFavorites($favorite_id){
        
        $sql = "select num_iid from ".$this->table_pre."goods_info where source = 1 and status = 1 and favorite_id = $favorite_id";
        
        $result = db_query_col($sql,$this->db,array(),$this->pdo);

        return $result;
    }
    //取出当日数据所有商品num_iid
    public function fetchNewGoodsNumId($favorite_id='',$limit=''){

        if($limit)$limit = " limit ".$limit;

        if($favorite_id)$favorite_id = " and favorite_id = $favorite_id";

        $sql = "select num_iid from ".$this->table_pre."goods where source = 1 and created_date = '".$this->date."' $favorite_id order by createdAt desc".$limit;

        $result = db_query_col($sql,$this->db,array(),$this->pdo);

        return $result;  

    }
    //取出现在上架/手动下架的所有商品num_iid
    public function fetchOnlineGoodsNumId($favorite_id=''){

        if($favorite_id)$favorite_id = "and favorite_id = $favorite_id";

        //$sql = "select num_iid from ".$this->table_pre."goods_online where source = 1 and (status = 1 or status = 5) $favorite_id";
        $sql = "select num_iid from ".$this->table_pre."goods_online where source = 1 and status in(".implode(",",GW_FAV_GOODS).") $favorite_id";
        //echo $sql;
        $result = db_query_col($sql,$this->db,array(),$this->pdo);

        return $result;  

    }   
    //插入新增的商品信息
    //新增 = 新入 - 线上（上架+手工下架）
    //以前失效下架的，又有新的添加为新品（先删除这些numid数据在增加，以免2个同num_iid）
    public function insertOnlineIncrGoods($incr_online_goods_list){

        if(!count($incr_online_goods_list))return;
        
        $sql_list = array();
        //先删除这些numid数据在增加，以免2个同num_iid 
        $sql_list[] = "replace into ".$this->table_pre."goods_online(status,source,num_iid,title,pict_url,small_images,item_url,category,category_id,favorite,favorite_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time) select 1,source,num_iid,title,pict_url,small_images,item_url,category,category_id,favorite,favorite_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time from ".$this->table_pre."goods where num_iid in (".implode(",",$incr_online_goods_list).")";

        //$r = db_insert($sql,$this->db,array(),$this->pdo);
        //先删除这些numid数据在增加，以免2个同num_iid
        //默认新增商品50分评分
        $sql_list[] = "replace into ".$this->table_pre."goods_info(status,source,is_coupon,is_new,is_sold,is_front,is_board,click,purchase,score,top,created_date,category_id,favorite_id,num_iid)select 1,1,0,1,0,0,0,0,0,50,0,created_date,category_id,favorite_id,num_iid from ".$this->table_pre."goods where num_iid in (".implode(",",$incr_online_goods_list).")";

        //$r = db_insert($sql,$this->db,array(),$this->pdo);
        $r = db_transaction($this->pdo,$sql_list);
        
        return $r;
    }

    //下架失效的商品
    public function updateOnlineOffGoods($off_online_goods_list){

    	 if(!count($off_online_goods_list))return;

    	 $list_con = implode(",",$off_online_goods_list);

    	 $sql_list = array();
    	 //!!*下架商品 需要同时更新2张表的status
    	 $sql_list[] = "update ".$this->table_pre."goods_online set status = 2  where source = 1 and num_iid in ($list_con)";  

    	 $sql_list[] = "update ".$this->table_pre."goods_info set status = 2 where source = 1 and num_iid in ($list_con)"; 

         $r = db_transaction($this->pdo,$sql_list);

         return $r;

    }

    //两者共同部分的更新,更新原来内容
    public function updateOnlineComGoods($com_online_goods_list){

         if(!count($com_online_goods_list))return;

         $list_con = implode(",",$com_online_goods_list);

         //!!*下架商品 需要同时更新2张表的status
         $sql = "replace into ".$this->table_pre."goods_online(status,source,num_iid,title,pict_url,small_images,item_url,category,category_id,favorite,favorite_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time) select 1,source,num_iid,title,pict_url,small_images,item_url,category,category_id,favorite,favorite_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time from ".$this->table_pre."goods where num_iid in ($list_con)";

         $r = db_execute($sql,$this->db,array(),$this->pdo);

         return $r;

     }

     //插入新的选品库类型
     public function insertFavoriteType($favorite_id,$favorite_name){
       
        $sql = "select count(0) from ".$this->table_pre."category_favorite_ref where favorite_id = $favorite_id and favorite_name = '$favorite_name'";
        //echo $sql;echo "<br>";
        $r = db_query_singal($sql,$this->db,array(),$this->pdo);
       
        if($r)return true;
        
        $sql = "replace into ".$this->table_pre."category_favorite_ref(favorite_id,favorite_name)values($favorite_id,'$favorite_name')";
       
        $r = db_execute($sql,$this->db,array(),$this->pdo);

        return $r;
     }


     public function fetchCategoryByFavoriteType($favorite_id,$favorite_name){

        $sql = "select category_id,category_name from ".$this->table_pre."category_favorite_ref where favorite_id = $favorite_id and favorite_name = '$favorite_name'";
        //echo $sql;
        $r = db_query_row($sql,$this->db,array(),$this->pdo);
        
        return $r;
    }



}