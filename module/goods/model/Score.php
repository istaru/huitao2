<?php



class Score extends GoodsModule {
	//单次处理数量
	protected $loop = 5000;

	protected $pdo;

	public function __construct(){
		   //定义了日期
        parent::__construct();

        $this->pdo = new ScorePdo();
	}


	public function index(){

	}



	//转化率加分
	/*
	* 100块 * 1买 / 100点击 = 1分
	* sum(real_pay) = real_pay * pruchase 
	* sum(real_pay)/clicks = real_pay/100(参考价格) * (pruchase/clicks*100(倍率))
	* 转化率(1%) * 实际支付/100块 = 1分
	* 单次评分最多+10分 x>10?10:x
	*/
	public function addPurchaseRateScore(){
		//商品转化率
		$goods_conver_rate = $this->_getGoodsConverRate($this->pdo->fetchGoodsIdLastHour());
			//print_r($goods_conver_rate);exit;
		$goods_info = $this->pdo->fetchGoodsPurchaseInfoLastHour();
		//统计结果的
		$deal_goods = array();
		//成功
		$success_rec = 0;
		//失败数量
		$fail_rec = 0;
		//echo count($goods_info);exit;
		foreach ($goods_info as $key => $info) {
					//有点击，无购买，可能要扣分
			if($info["click"]&&!$info["purchase"]){
				
				$clicks = $info["click"];
				//每小时，每50个点击为中线，每小时最多扣加分上线的25% 
				$score = abs($clicks-50)* (-0.01) < (GW_HOUR_ADD_SCORE_LIMIT * -0.25) ? GW_HOUR_ADD_SCORE_LIMIT * -0.25 : abs($clicks-50)* (-0.01);

			}//有分数，代表有价格，值得更新
			else{
				
				if($info["num_iid"]){
					//分母不能为0,没有点击有购买的 算100点击
					/*if(!$info["click"]){

						$info["click"] = 100;
						//echo $info["click"];echo $goods_info[$key]["click"] ;
					}*/	
					//大于0
					if(isset($goods_conver_rate[$info["num_iid"]])&&$goods_conver_rate[$info["num_iid"]])

						$conver_rate = $goods_conver_rate[$info["num_iid"]] > 1 ? 1 : $goods_conver_rate[$info["num_iid"]];

					else $conver_rate = 0.5;

					//分数 = 转化率 * 数量 * 单价 / 100					
					$score = $conver_rate * $info["fee"] / 20 > GW_HOUR_ADD_SCORE_LIMIT ? GW_HOUR_ADD_SCORE_LIMIT : $conver_rate * $info["fee"] / 20;		
					
					if(!$score)continue;

				}else continue;
			}
			//更新商品评分
			$r = $this->pdo->updateGoodsScore($info["num_iid"],number_format($score,2));

			$deal_goods[$info["num_iid"]] = number_format($score,2);

			if($r!==false)$success_rec++;

			else $fail_rec++;
		}
		print_r($deal_goods);
		//if($this->isRecord)
			echo "更新".$success_rec."件商品，失败".$fail_rec."件.";
		

	}
	//得到 num_iid=>conver_rate 的转化率
	protected function _getGoodsConverRate($num_iid){

		$conver_rate = array();

		$t_conver_rate = $this->pdo->fetchGoodsConverRate($num_iid);
		print_r($t_conver_rate);//exit;
		foreach ($t_conver_rate as $key => $value) {
			
			$conver_rate[$value["num_iid"]] = $value["convert_rate"];
		}

		return $conver_rate;

	}

	//上架后每天稳定扣分，保证新品的展示资格。
	//对象：所有上架商品，循环处理
	//规则：7天内1分1扣，7天以上2分1扣
	public function reduceOnlineDate(){
		//商品数
		$goods_nums = $this->pdo->fetchOnlineNum();

		$loop = ceil($goods_nums / $this->loop);
		//一次处理5000个
		for($i=0;$i<$loop;$i++){

			//$this->pdo->get

		}


	}
	

	//得到商品的上架时间
	protected function fetchGoodsOnlineDate(){

	}

	//填充商品分数的数据(小时内的点击销售情况).
	public function updateGoodsScoreInfo(){
		
		$data = $this->pdo->fetchGoodsPurchaseInfoLastHour();
	
		foreach ($data as $key => $value) {
			
			if($value["num_iid"]){

				$this->pdo->updateGoodsScoreInfo($value["num_iid"],$value["click"],$value["purchase"]);
			}

		}

	}

}


	class ScorePdo extends GoodsModule{

		public $table_pre = "ngw_";
	//	public $pdo;

   // public $db;

    public function __construct($isDebug=1){

        parent::__construct();

        $this->isDebug = $isDebug;

        $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

        $this->db = $this->isDebug?"shopping_new":"huitao";


        
    }

		//线上商品数量
		public function fetchOnlineNum(){

			$sql = "select count(0) from ".$this->table_pre."goods_info where status in(".implode(",",GW_SCORED_GOODS).")";

			return db_query_singal($sql,$this->db,array(),$this->pdo);

		}

			//获取上一小时商品的销售情况
		public function fetchGoodsPurchaseInfoLastHour(){
			

			/*$sql = "select b.num_iid,fee,purchase,click from 
				(select sum(fee) fee,count(0) purchase,num_iid from ".$this->table_pre."shopping_log where createdAt > '2017-01-02 14:09:17' group by num_iid)a 
			right JOIN
				(select sum(click) click,num_iid from ".$this->table_pre."click_log where createdAt > '2017-01-02 14:09:17' group by num_iid)b 
			on a.num_iid = b.num_iid";
			*/
			/**/
			$sql = "select b.num_iid,fee,count(0) purchase,click from ngw_shopping_log a JOIN 
				
				(select sum(click) click,num_iid from ngw_click_log where createdAt >= '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' group by num_iid)b 
			
					on a.num_iid = b.num_iid
	
	 		where a.createdAt >= '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' group by a.num_iid";
			//echo $sql;exit;
	 		$sql = "select b.num_iid,fee,count(0) purchase,click from ngw_shopping_log a JOIN 
				
				(select sum(click) click,num_iid from ngw_click_log where createdAt >= '2017-01-02 14:09:17' group by num_iid)b 
			
					on a.num_iid = b.num_iid
	
	 		where a.createdAt >= '2017-01-02 14:09:17' group by a.num_iid";
			
			//echo $sql;exit;
			return db_query($sql,$this->db,array(),$this->pdo);


		}

		//上一小时的下单的商品id
		public function fetchGoodsIdLastHour(){
			/*
			 $sql = "select distinct(num_iid) from ".$this->table_pre."shopping_log where createdAt > '". date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and num_iid > 0";
			*/
			

			$sql = "select distinct(num_iid) from ".$this->table_pre."shopping_log where createdAt > '2017-01-02 14:09:17' and num_iid > 0";
			
			return db_query_col($sql,$this->db,array(),$this->pdo);
		}

		//获取商品转化率
		public function fetchGoodsConverRate($num_iid=array()){

			$num_iid_con = "";

			if(is_array($num_iid)&&count($num_iid))$num_iid_con = " and num_iid in (".implode(",",$num_iid).")";

			$sql = "select IF(click > 0,purchase/click,0) convert_rate,num_iid from ".$this->table_pre."goods_info where status in (".implode(",",GW_SCORED_GOODS).")".$num_iid_con;
			//echo $sql;
			return db_query($sql,$this->db,array(),$this->pdo);
		}

		public function updateGoodsScore($num_iid,$score){

			$sql = "update ".$this->table_pre."goods_info set score = score + $score  where num_iid in ($num_iid)"; 
			//echo $sql;exit;
			$r = db_execute($sql,$this->db,array(),$this->pdo);

	        return $r;

		}
		//更新上架商品的点击销售情况
		public function updateGoodsScoreInfo($num_iid,$click=0,$purchase=0){

			$sql = "update ".$this->table_pre."goods_info set click = score + $click,purchase = purchase + $purchase  where num_iid in ($num_iid)"; 
			//echo $sql;exit;
			$r = db_execute($sql,$this->db,array(),$this->pdo);

	        return $r;

		}


	}
