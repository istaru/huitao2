<?php

abstract class NewGoods 
{

    public $pdo;

    public $db;

    public $date;

    public $adzone_id = "67202476";

    public $table_pre = "gw_";

    public $isDebug = 1;

	public function __construct(){
       
        $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

        $this->db = $this->isDebug?"shopping_new":"huitao";

        $this->date = isset($_GET["date"])&&!empty($_GET["date"])?$_GET["date"]:date("Y-m-d");
		
	}

    //各种类型商品数据获取
    abstract public function getGoodsData();

    //各种类型商品导入
    abstract public function inputGoods();


}


//Excel导入商品
class ExcelGoods extends NewGoods{

    public function getGoodsData(){

    }   
    //excel型实质是不需要代码导入的
    public function inputGoods(){



    }



}

//API导入选品库商品
class FavoriteGoods extends NewGoods{
    //用API取得数据（批量数据）
    // 
    public function getGoodsData(){
        
        $api = new TaoBaoApiController('23550152',"d27bdb2a9dba59cc20d7099f371d03d3");
        
        $r = $api->ibkUatmFavorites();
        //print_r($r);
        $favorites = array();
        //取得所有分类选品库
        if(isset($r["results"]["tbk_favorites"])){
            
            foreach ($r["results"]["tbk_favorites"] as $key => $val) {
                //分类id=>分类名
                $favorites[$val["favorites_id"]] = $val["favorites_title"];
            }
        }

        if(!count($favorites))return ssreturn($favorites,$msg='获取淘宝联盟选品库列表失败.',2,1) ;

        $param["platform"] = 1;
        $param["page_size"] = 100;
        $param["adzone_id"] = $this->adzone_id;
        
        //单个选品库，根据总数进行遍历次数控制。
        foreach ($favorites as $key => $value) {
            //字符串格式
            $param["favorites_id"] = $key."";
            $param["page_no"] = 1;
            $param["fields"] = "num_iid";
           // print_r($param);//exit;
            $r = $api->tbkUatmFavoritesItem($param);
            //print_r($r);exit;

            if(isset($r["results"]["uatm_tbk_item"])){
                //总数据数
                $total_results = $r["total_results"];
                
                do{ 
                    //单次取api数据的结果
                    $data = $this->_getUnitGoodsData($api,$param);

                    $param["page_no"] += 1;

                    //print_r($data);
                    $this->savaGoodsData($data,array($key,$value));

                   // $attrs = array_keys($data[0]);print_r($attrs);

                    //exit;
                //翻页到底了
                }while($total_results >= $param["page_no"] * count($r["results"]["uatm_tbk_item"]));
                //echo count($r["results"]["uatm_tbk_item"]);exit;
            }
            else return ssreturn($favorites,$msg='获取获取淘宝联盟选品库的宝贝信息失败.',2,1) ;

        }
       
       //print_r($api->tbkUatmFavoritesItem());
    }  
    //getGoodsData内部使用，单次取api数据的结果
    protected function _getUnitGoodsData($api,$param){

        $param["fields"] = "num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type";
            
        $r = $api->tbkUatmFavoritesItem($param);

        if(isset($r["results"]["uatm_tbk_item"])){

            return $r["results"]["uatm_tbk_item"];

        }

    }

    //存储商品信息
    //@$category_info : $key => $value 
    public function savaGoodsData($data,$category_info){

        $this->getOnlineGoods();

        list($category_id,$category) = $category_info;

        $sql = "insert into ".$this->table_pre."goods_online (status,source,num_iid,title,pict_url,item_url,category,category_id,price,volume,rating,seller_id,store_name,store_type,discount,deal_price,created_date,event_start_time,event_end_time)values";
        
        $values = array();
       
        $bindParam = array();
        
        $c = '';
       
        foreach ($data as $k => $v) {

            $c .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),";
            
            $bindParam = array_merge($bindParam,array(1,1,$v["num_iid"],$v['title'],$v['pict_url'],$v['item_url'],$category,$category_id,$v["reserve_price"],$v["volume"],$v["tk_rate"],$v["seller_id"],$v["shop_title"],$v["user_type"],number_format($v["zk_final_price"]/$v["reserve_price"]*100,2),$v["zk_final_price"],$this->date,$v["event_start_time"],$v["event_end_time"]));
            # code...
            
        }

        $sql = $sql.trim($c,",");
        //echo $sql;
        //清楚当日的选品库来源的该分类的商品
        $d_sql = "delete from ".$this->table_pre."goods_online where source = 1 and created_date = ? and category_id = ?";

        $d_bindParam = array($this->date,$category_id);
        
        $pdo = $this->pdo;

        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $isBad = 0;

        try{ 

            $pdo->beginTransaction(); 

            $rt = db_execute($d_sql,$this->db,$d_bindParam,$this->pdo);
            
            $r = db_insert($sql,$this->db,$bindParam,$this->pdo);


            if(false===$r){

                print_r($pdo->errorInfo());
                print_r($pdo->errorCode());
                //echo $insert_sql;
                $isBad =1;
            }

            if($isBad)return false;

        
            $pdo->commit(); 


         }catch(PDOException $e){
            echo date("Y-m-d H:i:s").":".$e->getMessage()."\r\n";
            $pdo->rollback();
        }  

        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        
/*
             Array ( [0] => event_end_time [1] => event_start_time [2] => item_url [3] => nick [4] => num_iid [5] => pict_url [6] => provcity [7] => reserve_price [8] => seller_id [9] => shop_title [10] => small_images [11] => status [12] => title [13] => tk_rate [14] => type [15] => user_type [16] => volume [17] => zk_final_price [18] => zk_final_price_wap ) 
       
       id,status,num_iid,title,pict_url,item_url,category,category_id,promotion_url,price,volume,rating,seller_id,seller_name,store_name
store_type,discount,deal_price,created_date
*/

    }
    
    //取出现在上架的商品
    public function getOnlineGoods(){
        
        $sql = "select num_iid from ".$this->table_pre."goods_online where created_date = ?";
        echo $sql;
        $result = db_query_col($sql,$this->db,array(date("Y-m-d",strtotime($this->date."-1 day"))),$this->pdo);

    }

    //下架失效的商品
    protected function _delInvaildGoods(){



    }

    //添加新的商品
    protected function _addNewGoods(){



    }

    //
    public function inputGoods(){

        
    }

}
//手动添加商品
class OperationGoods extends NewGoods{
    //添加的数据是一条条的获取
    public function getGoodsData(){

    }  

    public function inputGoods(){

        
    }



}