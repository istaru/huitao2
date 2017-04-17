<?php

class FilterScore extends NewGoods{

    public $goods_info;

	public function __construct(){

		parent::__construct();


	}

    public function getGoodsData(){
        
    }   
    
    public function onlineGoods(){

    }
    //根据策略号计算当前商品的评分
    public function index($strategy_id,$goods_info){

        $stime=microtime(true); 

        $this->goods_info = $goods_info;
        //取方法类型名
        $sql = "select method from gw_filter order by id";

        $filter_list = db_query_col($sql,$this->db,array(),$this->pdo);
        //取改策略的评分因子，比率
        $sql = "select type,rating from gw_filter_detail where strategy_id = ?";
        
        $rt = db_query($sql,$this->db,array($strategy_id),$this->pdo);
        //print_r($rt);
        $score = 0;

        foreach ($rt as $key => $value) {

            if(!$value)continue;
            
            //当前评分因素号对应的方法名，如果方法存在，直接算评分
            $method = $filter_list[$value["type"] - 1]."Score";
           
            if(method_exists($this,$method)){
                //echo "m:".$method;
                $r = call_user_func_array(array($this,$method),array($this->goods_info)) * $value["rating"]; 
                //echo $r.",<br>";
                $score = $r + $score;

            }

        }

        echo $score.",time:".(microtime(true)-$stime);

    }

    //不同分类提供的积分不同
    //@param:分类号（子类）
    public function categoryScore($goods_info){

        if(isset($goods_info["category_id"]))

            $category_id = $goods_info["category_id"];

         else return 0;
        //把这个分类的，评分和父类评分取出，选高的。
        //这里不考虑添加评分的问题
        $sql = "select if(score>pscore,score,pscore)score from gw_category where category_id = ?";

        $rt = db_query_singal($sql,$this->db,array($category_id),$this->pdo);

        return $rt;
    }

    //佣金比
    public function ratingScore($goods_info){

        if(isset($goods_info["rating"]))

            $rating = $goods_info["rating"];

         else return 0;

        return $rating;
    }

    //价格
    //@param:价格，优惠券门槛，优惠券力度
    public function priceScore($goods_info){

        if(isset($goods_info["price"])&&isset($goods_info["limited"])&&isset($goods_info["reduce"])){

            $price = $goods_info["price"];

              $limited = $goods_info["limited"];

                $reduce = $goods_info["reduce"];

        }
         else return 0;
        //如果门槛比价格高，直接没券用
        if($price<$limited)return 0;
        //理论成交价
        $deal_price = $price - $reduce;

        switch ($deal_price) {
            //20以下
            case $deal_price <= 20:
                //以5为界，修正分数,5块以下扣分
                return 50 + ($deal_price - 5) ;
            break;
            //20~50元
            case $deal_price <= 50:
                
                return 65 + ($deal_price - 20) / 5;
            break;
            
            case $deal_price <= 100:
                //以75为界，修正分数
                return 40 - ($deal_price / 10);
            break;

            case $deal_price > 100:

                return 30;
            break;

            default:
                # code...
            break;
        }
    }

    //商铺类型
    //平台类型 0-淘宝 1-天猫
    public function storeType($goods_info){

        if(isset($goods_info["store_type"]))

            $store_type = $goods_info["store_type"];

         else return 0;

        return $store_type ? 50 : 0;

    }

    //商品月销量
    public function volumeScore($goods_info){

         if(isset($goods_info["volume"]))

            $volume = $goods_info["volume"];

         else return 0;

        return 50 - abs((10000 - $volume)) / 1000;

    }

    //邮费
    public function postFeeScore($goods_info){

        $post_fee = 0;

         if(isset($goods_info["post_fee"]))

            $post_fee = $goods_info["post_fee"];

        else return 0;

        return $post_fee ? 0 : 50;

    }

    //top - 人工修正的
    public function topScore($goods_info){

         if(isset($goods_info["top"]))

            $top = $goods_info["top"];

        else return 0;

        return $top;

    }




}