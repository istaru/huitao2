<?php

abstract class NewGoods extends GoodsModule
{

    public $pdo;

    public $adzone_id = "67202476";

    public $isDebug = 1;

    public $isRecord = 1;

    public $api;

	public function __construct(){
        //定义了日期
        parent::__construct();
       
       
	}

    //各种类型商品数据获取
    abstract public function getGoodsData();

    //上架
    abstract public function onlineGoods();


}


//Excel导入商品
class ExcelGoods extends NewGoods{

    public function __construct(){
        //定义了日期
        parent::__construct();
        
        $this->pdo = new ExcelGoodsPdo($this->isDebug);

    }

    public function getGoodsData(){

        //$this->_dealCoupon();exit;

        $attrs = $this->_getTempTableAttrs();

        for($i=0;$i<5;$i++){
            //一次取2000条数据
            //直接去掉无法提取的分类
            $datas = $this->pdo->fetchCategoryInfo($attrs,20,$i*2000);
            
            //print_r($datas);//exit;
            //$this->_dealCategory();
            foreach ($datas as $key => $value) {

                if(!$value["num_iid"])continue;
                //处理分类
                list(
                $datas[$key]["category"],
                $datas[$key]["category_id"],
                $datas[$key]["favorite"],
                $datas[$key]["favorite_id"]) = $this->_dealCategory($value);
                //处理商铺分类
                $datas[$key]["store_type"] = $this->_dealStoreType($value["store_type"]);
                
                $datas[$key]["created_date"] = $this->date;
                //处理优惠券
                list(
                $datas[$key]["limited"],
                $datas[$key]["reduce"],
                $datas[$key]["deal_price"],
                $datas[$key]["discount"],
                $datas[$key]["coupon_get_url"] ) = $this->_dealCoupon($value);

            }
            //print_r($datas);exit;
            $this->pdo->InsertEffortsToGoods($datas);exit;

        }

    } 

    //获取零时表并转化属性
    protected function _getTempTableAttrs(){

        $r = $this->pdo->ckTempTableExist();
        //存在
        if($r){

             $column = $this->pdo->fetchColumn();
             //print_r($column);
             //行数相同
             if(count($column) == 22){

                $attrs = "";

                foreach ($column as $key => $val) {
                    
                    if(!$val||!isset($this->pdo->attrs[$key])){
                        
                        return ssreturn($column,$msg='构成语句失败.',2,1);

                        break;
                    }
                  
                    $attrs .= $val." as ".$this->pdo->attrs[$key].",";                    

                }

                $attrs = trim($attrs,",");

                //print_r($this->pdo->fetchData($attrs));
                return $attrs;
              
             }
        }
        //不存在
        else {

            return ssreturn($this->pdo->temp_table,$msg='导入临时表获取失败.',2,1);
        }
    }  

    //处理分类,直接过滤掉没有映射的分类
    protected function _dealCategory($value){
        //category,category_id,favorite,favorite_id
        return array($value["cname"],$value["cid"],$value["category"],0);


    }
   // /0-天猫 1-淘宝
    protected function _dealStoreType($store_type){

        return $store_type == "天猫" ? 0 : 1;

    }
    //处理分析优惠券
    protected function _dealCoupon($value){

        preg_match_all("|\d+|i",$value['val'],$result);
        //优惠券门槛
        $limit = 0;
        //优惠券力度
        $reduce = 0;

        if(count($result[0])){

            if(count($result[0]) == 1){

                $limit = 0;

                $reduce = $result[0][0];
            }else {

                $limit = $result[0][0];

                $reduce = $result[0][1];
            }
        }
        //echo $limit.",".$reduce;
       
        $deal_price = $value['price'] - $reduce;

        $discount = number_format($deal_price/$value['price']*100,2);

        $coupon_get_url = "https://h5.m.taobao.com/ump/coupon/detail/index.html?sellerId=".$value["seller_id"]."&activityId=".$value["coupon_id"]."&global_seller=false&currency=CNY";
        
        return array($limit,$reduce,$deal_price,$discount,$coupon_get_url);
    }


    //excel型实质是不需要代码导入的
    public function onlineGoods(){



    }



}

//API导入选品库商品
class FavoriteGoods extends NewGoods{

    public $transaction_tools;

    public function __construct(){
        //定义了日期
        parent::__construct();
        //断点操作工具
        $this->transaction_tools = new TransactionTools();

        $this->pdo = new FavoriteGoodsPdo($this->isDebug,$this->adzone_id);

    }

    protected function _getFavoriteData(){
            //获取API重试次数
        $retry = 5;

        $cur_retry = 0;

        do{

            $this->api = new TaoBaoApiController('23550152',"d27bdb2a9dba59cc20d7099f371d03d3");
            $temp = "d".$cur_retry."7bdb2a9dba59cc20d7099f371d03d3";
            $this->api = new TaoBaoApiController('23550152',$temp);
            $r = $this->api->ibkUatmFavorites();
            //print_r($r);exit;
            $favorites = array();
            //取得所有分类选品库
            if(isset($r["results"]["tbk_favorites"])){ 
                //开始记录事务记录
                if($this->isRecord)$this->transaction_tools->writeRecord();

                foreach ($r["results"]["tbk_favorites"] as $key => $val) {
                    //分类id=>分类名
                    $favorites[$val["favorites_id"]] = $val["favorites_title"];
                    //检查这个选品库分类存在是否，不存在插入
                    $r = $this->pdo->insertFavoriteType($val["favorites_id"],$val["favorites_title"]);

                }
                //单个任务成功记录
                //必须没有回滚点
                //echo $this->transaction_tools->listenProcess();exit;
                if($this->isRecord)

                    $this->transaction_tools->addMissionLog("第".($cur_retry+1)."次选品库".count($favorites)."个分类获取");

                break;
            }
            //echo $cur_retry++;
            //执行了5次，还是没有结果
            if($cur_retry++==$retry){

                if($this->isRecord)$this->transaction_tools->addErrorLog('获取淘宝联盟选品库列表失败.');

                return ssreturn($favorites,$msg='获取淘宝联盟选品库列表失败.',2,1) ;
            }//开启循环的几个条件

        }while($this->isRecord&&!count($favorites)&&$cur_retry<$retry);

        return $favorites;

    }
    //用API取得数据（批量数据）
    // 
    public function getGoodsData(){

        $favorites = $this->_getFavoriteData();
        //如果有回滚点，去掉已经处理完的任务。
        if($this->isRecord){

            $rollback_sign = $this->transaction_tools->listenProcess($favorites);
            //echo $rollback_sign;
            if($rollback_sign>0){
                //干掉已经成功操作的分类
                foreach ($favorites as $k => $v) {

                    if($v!=$rollback_sign)unset($favorites[$k]);

                    else break;
                }
            }
        }
        "当前分类：".print_r($favorites);//exit;
        //获取API重试次数
        $retry = 5;

        $cur_retry = 0;
        //exit;
        $param["platform"] = 1;
        //!!*最多100，商品最多200个，传入参数需要字符串化
        $param["page_size"] = 100;

        $param["adzone_id"] = $this->adzone_id;
        //所有失效的商品id,用a,b,c连接
        $invalid_goods_list = '';
        //总处理数据数
        $total_dealing_data = 0;
       // for($n=0;$n<50;$n++){
        //①单个选品库，根据总数进行遍历次数控制。
        //有时候多个商品在几种选品库里都有，后者会覆盖前者
        foreach ($favorites as $key => $value) {
            //取出这选品库分类的分类
            $categroy_result = $this->pdo->fetchCategoryByFavoriteType($key,$value);
            //print_r($categroy_result);echo "<br>";//exit;
            //!!*没有值，说明没绑定到分类上
            if(!isset($categroy_result["category_id"])||!$categroy_result["category_id"]){

                if($this->isRecord)$this->transaction_tools->addErrorLog('选品库分类'.$value.'获取映射失败.');
                
                continue;
            }
            //字符串格式
            $param["favorites_id"] = $key."";

            $param["page_no"] = 0;

            $param["fields"] = "num_iid";
            //分类处理数据数
            $favorite_dealing_data = 0;
           // print_r($param);exit;
            $r = $this->api->tbkUatmFavoritesItem($param);
            //echo 1;;print_r($r);exit;
            if(isset($r["results"]["uatm_tbk_item"])){
                //开始分类任务的事务记录
                if($this->isRecord)$this->transaction_tools->beginMission($value);
                //总数据数
                $total_results = $r["total_results"];
                //单类处理总结果数
                //$_dealing_data = 0;    
               
                do{ 
                    //翻下页查询
                    $param["page_no"] += 1;

                    $cur_retry = 0;

                    do{
                        //单次取api数据的结果
                        $data = $this->_getUnitGoodsData($this->api,$param);
                        //print_r($data);//exit;
                        $cur_retry = $cur_retry + 1;

                        //多次获取API失败，提示错误
                        if($cur_retry > $retry){

                            $error_msg = '获取淘宝联盟选品库第'.$param["page_no"].'页的宝贝信息失败.';

                            return ssreturn($favorites,$error_msg,2,1) ;
                        }

                    }while(false === $data);
                    //print_r($data);//exit;
                    //插入非失效信息到商品库表,返回其中失效的商品num__id
                    //print_r($categroy_result);exit;
                    list($t_invalid_goods_list,$t_favorite_dealing_data) = $this->pdo->InsertEffortsToGoods(
                        $data,array($key,$value),array_values($categroy_result)
                    );
                    //echo $t_favorite_dealing_data."<br>";
                    //添加失效商品号
                    $invalid_goods_list .= $t_invalid_goods_list;
                    //统计处理数据数
                    $favorite_dealing_data += $t_favorite_dealing_data;

                //exit;
                //翻页到底了
                //echo $total_results;echo $param["page_no"] * count($r["results"]["uatm_tbk_item"]);
                //!!*似乎删除下架的商品并没有马上被删除，但是总件数被马上清除了。
                }while($total_results > $param["page_no"] * count($r["results"]["uatm_tbk_item"]));
                //
                //完成分类任务的事务记录
                if($this->isRecord){
                    $this->transaction_tools->endMission();
                   // echo "选品库:".$value."获取".$favorites_success."条数据.";
                    echo "选品库分类：".$value."获取".$favorite_dealing_data."条数据，忽略".($total_results-$favorite_dealing_data)."条数据.<br>";
                    $this->transaction_tools->addLog("选品库分类：".$value."获取".$favorite_dealing_data."条数据，忽略".($total_results-$favorite_dealing_data)."条数据.");
                }//echo count($r["results"]["uatm_tbk_item"]);exit;
            }
            else {

                $error_msg = '获取'.$value.'选品库的宝贝信息失败.';

                if($this->isRecord)$this->transaction_tools->addErrorLog($error_msg);

                return ssreturn($favorites,$error_msg,2,1) ;
            }// echo $invalid_goods_list;
               //exit;
               //统计总处理数
            $total_dealing_data += $favorite_dealing_data;

            //失效的商品num__id，删除多余：
            //失效商品（相对昨天少了2块：1.被手动删除的商品 2.失效商品，多的是新增的商品）
            //这里的失效只是API中status=0的数据
            $invalid_goods_list = explode(",",trim($invalid_goods_list,","));
            //print_r($invalid_goods_list);
                //if($this->isRecord){echo "本次执行总处理：".$total_dealing_data."条数据，忽略".count($invalid_goods_list)."条数据.<br>";}      
            //有效新入 = 新入 - 无效（貌似多余，以防万一）
            //将本次操作的数量，作为限制，根据时间倒叙。
            $valid_goods_list = array_diff($this->pdo->fetchNewGoodsNumId($key),$invalid_goods_list);  
                if($this->isRecord){echo "新入：".count($valid_goods_list);echo "条数据<br>";}    
            //单个选品库操作，需要清空invalid_goods_list
            $invalid_goods_list = "";

            //----②添加新增商品start----//
            //新增 = 新入 - 线上（上架+手工下架）
            $online_goods_list = $this->pdo->fetchOnlineGoodsNumId($key);
            //以前失效下架的，又有新的添加为新品（先删除这些numid数据在增加，以免2个同num_iid）
            if($this->isRecord){echo "线上：".count($online_goods_list);echo "条数据<br>";}

            $incr_online_goods_list = array_diff($valid_goods_list,$online_goods_list);
                if($this->isRecord){echo "新增：".count($incr_online_goods_list)."条数据";}

            $r = $this->pdo->insertOnlineIncrGoods($incr_online_goods_list);
                if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}
            //exit;
            //----③下架失效商品start----//
            //下线 = 线上（上架+手工下架）- 新入 = (运营手动删除选品库商品 + API失效商品)
            $off_online_goods_list = array_diff($online_goods_list,$valid_goods_list);
                if($this->isRecord){echo "下架：".count($off_online_goods_list)."条数据";}
            $r = $this->pdo->updateOnlineOffGoods($off_online_goods_list);
                if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}
            //exit;
            //④更新 = 新入 && 线上
            $com_online_goods_list = array_intersect($valid_goods_list,$online_goods_list);
                if($this->isRecord){echo "更新信息：".count($com_online_goods_list)."条数据";}
            $r = $this->pdo->updateOnlineComGoods($com_online_goods_list);
                if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}

           
            
        }
        return ssreturn(1,'操作成功',1,1) ;
       // }
       // 
       // 
       // 
       /*
        //---------添加新增商品（可能1商品有多个分类）end------------//
        //exit; 
        //!!*这里有个比较大的风险，导入数据比真实少很多，会导致大量未下架商品下架，做个限制，不能下架25%
        $online_goods_list = $this->pdo->fetchOnlineGoodsNumId();
         //当日线上数据,比较这次处理的数据数，超过25%禁止操作
        if(count($online_goods_list)>=$total_dealing_data*1.25){
            $error_msg = "本次操作数据($total_dealing_data)小于线上(".count($online_goods_list).")商品25%以上.";
            if($this->isRecord)$this->transaction_tools->addErrorLog($error_msg);
            return ssreturn($favorites,$error_msg,2,1) ;
        }
        //失效的商品num__id，删除多余：
        //失效商品（相对昨天少了2块：1.被手动删除的商品 2.失效商品，多的是新增的商品）
        //这里的失效只是API中status=0的数据
        $invalid_goods_list = explode(",",trim($invalid_goods_list,","));
        //print_r($invalid_goods_list);
        if($this->isRecord){echo "本次执行总处理：".$total_dealing_data."条数据，忽略".count($invalid_goods_list)."条数据.<br>";}      
        //有效新入 = 新入 - 无效（貌似多余，以防万一）
        //将本次操作的数量，作为限制，根据时间倒叙。
        $valid_goods_list = array_diff($this->pdo->fetchNewGoodsNumId($total_dealing_data),$invalid_goods_list);  
            if($this->isRecord){echo "本日新入：".count($valid_goods_list);echo "条数据<br>";}      
        //----②添加新增商品start----//
        //新增 = 新入 - 线上（上架+手工下架）
        //以前失效下架的，又有新的添加为新品（先删除这些numid数据在增加，以免2个同num_iid）
        if($this->isRecord){echo "本日线上：".count($online_goods_list);echo "条数据<br>";}

        $incr_online_goods_list = array_diff($valid_goods_list,$online_goods_list);
            if($this->isRecord){echo "新增：".count($incr_online_goods_list)."条数据";}

        $r = $this->pdo->insertOnlineIncrGoods($incr_online_goods_list);
            if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}
        //exit;
        //----③下架失效商品start----//
        //下线 = 线上（上架+手工下架）- 新入 = (运营手动删除选品库商品 + API失效商品)
        $off_online_goods_list = array_diff($online_goods_list,$valid_goods_list);
            if($this->isRecord){echo "下架：".count($off_online_goods_list)."条数据";}
        $r = $this->pdo->updateOnlineOffGoods($off_online_goods_list);
            if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}
        //exit;
        //④更新 = 新入 && 线上
        $com_online_goods_list = array_intersect($valid_goods_list,$online_goods_list);
            if($this->isRecord){echo "更新信息：".count($com_online_goods_list)."条数据";}
        $r = $this->pdo->updateOnlineComGoods($com_online_goods_list);
            if($this->isRecord){echo $r ? "成功。<br>" : "失败。<br>";}
        exit;

        */
        //$new_goods_list = array_merge($invalid_goods_list,$valid_goods_list);
        //print_r($new_goods_list);
       //print_r($api->tbkUatmFavoritesItem());
    }  
    //getGoodsData内部使用，单次用API取数据的结果
    protected function _getUnitGoodsData($api,$param){

        $param["fields"] = "num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type";
             //   print_r($param);
        $r = $api->tbkUatmFavoritesItem($param);
        //print_r($r);
        if(isset($r["results"]["uatm_tbk_item"])){

            return $r["results"]["uatm_tbk_item"];

        }

        return false;

    }
/*
             Array ( [0] => event_end_time [1] => event_start_time [2] => item_url [3] => nick [4] => num_iid [5] => pict_url [6] => provcity [7] => reserve_price [8] => seller_id [9] => shop_title [10] => small_images [11] => status [12] => title [13] => tk_rate [14] => type [15] => user_type [16] => volume [17] => zk_final_price [18] => zk_final_price_wap ) 
       
       id,status,num_iid,title,pict_url,item_url,category,category_id,promotion_url,price,volume,rating,seller_id,seller_name,store_name
store_type,discount,deal_price,created_date
*/

    
    
    //取出某个分类下现在上架的所有商品
    public function getOnlineGoods($favorites_id){
        
        $sql = "select num_iid from ".$this->table_pre."goods_info where source = 1 and status = 1 and category_id = $favorites_id";
        
        $result = db_query_col($sql,$this->db,array(),$this->pdo);

    }

    //下架失效的商品
    protected function _delInvaildGoods(){



    }

    //添加新的商品
    protected function _addNewGoods(){



    }

    //
    public function onlineGoods(){

        
    }

}
//手动添加商品
class OperationGoods extends NewGoods{
    //添加的数据是一条条的获取
    public function getGoodsData(){

    }  

    public function onlineGoods(){

        
    }



}