<?php



class Score extends GoodsModule {
	//单次处理数量
	protected $loop = 5000;

	protected $pdo;

	//热卖分数线
	protected	$is_sold_score = 80;
		//移除出热卖分数线
	protected	$is_not_sold_score = 60;
		//最高分数
	protected	$limited_score = 120;
		//最低分数
	protected	$limited_low_score = 40;
	//到达最低分数后，扣分开始打折
	protected $reduce_score_rating = 0.3;
	//修正算分倍率
	protected $add_score_rating = 4;

	public function __construct(){
		   //定义了日期
        parent::__construct();

        $this->pdo = new ScorePdo();
        //调试功能
        $this->debug = 1;
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
	
		$goods_info = $this->pdo->fetchGoodsPurchaseInfoLastHour();
		if($this->debug)print_r($goods_info);//exit;
		//统计结果的
		$deal_goods = array();
		//成功
		$success_rec = 0;
		//失败数量
		$fail_rec = 0;
		//print_r($goods_info);//exit;
		if(!count($goods_info))ssreturn(0,"上个小时数据查询失败.",2,2);
		//echo count($goods_info);exit;
		foreach ($goods_info as $key => $info) {
					//有点击，无购买，可能要扣分
			if($info["click"]&&!$info["purchase"]){
				
				$clicks = $info["click"];
				//每小时，每50个点击为中线，每小时最多扣加分上线的25% 
				$score = abs($clicks-25)* (-0.01) < (GW_HOUR_ADD_SCORE_LIMIT * -0.25) ? GW_HOUR_ADD_SCORE_LIMIT * (-0.25) : abs($clicks-25)* (-0.01);



			}//有分数，代表有价格，值得更新
			else{
				
				if($info["num_iid"]){
					//分母不能为0,没有点击有购买的 算100点击
					/*if(!$info["click"]){

						$info["click"] = 100;
						//echo $info["click"];echo $goods_info[$key]["click"] ;
					}*/	
						//商品转化率
					$goods_conver_rate = $this->_getGoodsConverRate($this->pdo->fetchGoodsIdLastHour());
					if($this->debug)print_r($goods_conver_rate);//exit;
					//大于0
					if(isset($goods_conver_rate[$info["num_iid"]])&&$goods_conver_rate[$info["num_iid"]])

						$conver_rate = $goods_conver_rate[$info["num_iid"]] > 1 ? 1 : $goods_conver_rate[$info["num_iid"]];

					else $conver_rate = 0.5;

					

					//分数 = 转化率(平均就是1%) * 数量 * 单价 *	修正倍率（4倍）				
					$score = $conver_rate * $info["fee"] * $this->add_score_rating;
					if($this->debug)echo "fee:".$info["fee"]."尝试修正分数：".$score;
					//购买最多增加单小时上限的分数
					if($score > GW_HOUR_ADD_SCORE_LIMIT){

						$score = GW_HOUR_ADD_SCORE_LIMIT ;
						//到达峰值的时候，小额商品热销修正
						if($info["fee"]<20)$score = $info["fee"]/20 * $score;
					}
					//购买至少加0.5分
					//if($score < 1)$score = 1;
					if($this->debug)echo "最终尝试修正分数：".$score."<br>";
					if(!$score)continue;

				}else continue;
			}

			$goods_score_info = $this->pdo->fetchGoodScore($info["num_iid"]);
			//print_r($goods_score_info);
			//没这个商品在info表中
			if(!count($goods_score_info))continue;
			//得到当前分数
			list($cur_score,$is_sold) = array_values($goods_score_info);
			//是否超过分数上线&分数是加分
			if($cur_score>=$this->limited_score&&$score>=0)continue;
			if($this->debug){echo $this->is_sold_score.",c:".($cur_score+$score);echo ",issold:$is_sold,";echo $info["num_iid"];echo "<br>";}
			//低于最低分 扣分衰弱
			if($cur_score<=$this->limited_low_score&&$score<0){
				if($this->debug){echo "扣分:".($score);echo "衰弱:";echo ($score * $this->reduce_score_rating);echo "<br>";}
				$score = $score * $this->reduce_score_rating;
			}
			//商品分数获得后，非热卖&超过一定分值标示 商品为热卖
			if(!$is_sold&&$cur_score+$score>=$this->is_sold_score){
				if($this->debug){echo "num_iid".$info["num_iid"];echo "<br>";}
				$r = $this->pdo->updateGoodsIsSold($info["num_iid"],1);
					
			}
			//商品分数获得后，热卖&低于一定分值标示 商品为非热卖
			if($is_sold&&$cur_score+$score<=$this->is_not_sold_score)

					$r = $this->pdo->updateGoodsIsSold($info["num_iid"],0);

			//更新商品评分
			$r = $this->pdo->updateGoodsScore($info["num_iid"],number_format($score,2));

			$deal_goods[$info["num_iid"]] = number_format($score,2);

			if($r!==false)$success_rec++;

			else $fail_rec++;
		}
		if($this->debug)print_r($deal_goods);
		//if($this->isRecord)
			echo "更新".$success_rec."件商品，失败".$fail_rec."件.";
		

	}
	//得到 num_iid=>conver_rate 的转化率
	protected function _getGoodsConverRate($num_iid){
		//print_r($num_iid);exit;
		$conver_rate = array();

		$t_conver_rate = $this->pdo->fetchGoodsConverRate($num_iid);
		//print_r($t_conver_rate);//exit;
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
		//记录上个小时的数据
		$this->pdo->insertHourReport();
		//查询出来
		$data = $this->pdo->fetchGoodsPurchaseInfoLastHour();
		//print_r($data);
		try{
			foreach ($data as $key => $value) {
				
				if($value["num_iid"]){

					$this->pdo->updateGoodsScoreInfo($value["num_iid"],$value["click"],$value["purchase"]);
					
				}

			}

			return 1;

		}catch(Exception $e){

			ssreturn(0,$e->getMessage(),2,2);
		}

	}

}


	class ScorePdo extends GoodsModule{

		public $table_pre = "ngw_";
	//	public $pdo;

   // public $db;

    public function __construct($isDebug=1){

        parent::__construct();

        //$this->isDebug = $isDebug;

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



			
			//当前几点
			$last_hour_num = date('H',strtotime(date('Y-m-d H:i:s')." -1 hour"));

			$sql = "select num_iid,sum(fee/amount) fee,sum(purchase) purchase,sum(click) click from ngw_hour_report where hour = ".$last_hour_num." and report_date = '".date("Y-m-d")."' GROUP BY num_iid";
			//echo $sql;
			return db_query($sql,$this->db,array(),$this->pdo);
			/*
			$sql = "select b.num_iid,fee,count(0) purchase,click from ".$this->table_pre."shopping_log a JOIN 
				
				(select sum(click) click,num_iid from ".$this->table_pre."click_log where createdAt between '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and '".date('Y-m-d H')."' group by num_iid)b 
			
					on a.num_iid = b.num_iid
	
	 		where a.createdAt between '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and '".date('Y-m-d H')."' group by a.num_iid";
			echo $sql;exit;// 测试数据用	
	 		$sql = "select b.num_iid,fee,count(0) purchase,click from ".$this->table_pre."shopping_log a JOIN 
				
				(select sum(click) click,num_iid from ".$this->table_pre."click_log where createdAt between '2017-01-02 14:09:17' and '".date('Y-m-d H')."' group by num_iid)b 
			
					on a.num_iid = b.num_iid
	
	 		where a.createdAt between '2017-01-02 14:09:17' and '".date('Y-m-d H')."' group by a.num_iid";
			
	 		
	 		$sql = "select sum(click) click,num_iid from ".$this->table_pre."click_log where createdAt between '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and '".date('Y-m-d H')."' group by num_iid";
	 		echo $sql;//exit;// 测试数据用	
	 		$click_info = db_query($sql,$this->db,array(),$this->pdo);

	 		$sql = "select fee,count(0) purchase,num_iid from ".$this->table_pre."shopping_log where createdAt between '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and '".date('Y-m-d H')."' group by num_iid";
	 			// 测试数据用	
	 		$sql = "select fee,count(0) purchase,num_iid from ".$this->table_pre."shopping_log where createdAt between '2017-03-20 16' and '2017-04-21 17' group by num_iid";
	 		echo $sql;exit;// 测试数据用	
	 		$purchase_info = db_query($sql,$this->db,array(),$this->pdo);

	 		

			//echo $sql;exit;
			return db_query($sql,$this->db,array(),$this->pdo);
			*/

		}
		//插入单个小时的数据
		public function insertHourReport(){
			//上个小时
			$last_hour = date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"));
			//当前几点
			$last_hour_num = date('H',strtotime($last_hour.":0:0"));
			//echo $last_hour_num;

			$sql_list = array();
			//点击的存入
			$sql_list[] = "replace into ".$this->table_pre."hour_report(click,num_iid,type,hour,report_date,createdAt) select sum(click) click,num_iid,type,$last_hour_num,report_date,createdAt from ".$this->table_pre."click_log where createdAt between '".$last_hour."' and '".date('Y-m-d H')."' group by num_iid,type";
			//购买的存入
			$sql_list[] = "replace into ".$this->table_pre."hour_report(num_iid,type,purchase,hour,report_date,fee,benifit,amount,createdAt) select num_iid,type,count(0) purchase,$last_hour_num,report_date,fee,benifit,IF(ISNULL(amount),1,amount) amount,createdAt from ".$this->table_pre."shopping_log where createdAt between '".$last_hour."' and '".date('Y-m-d H')."' group by num_iid,type";
			//print_r($sql_list);

			$r = db_transaction($this->pdo,$sql_list,array());

			return $r;

		}

		//上一小时的下单的商品id
		public function fetchGoodsIdLastHour(){
			
			$sql = "select distinct(num_iid) from ".$this->table_pre."shopping_log where createdAt between '".date('Y-m-d H',strtotime(date('Y-m-d H:i:s')." -1 hour"))."' and '".date('Y-m-d H')."' and num_iid > 0";
			
			//echo $sql;

			//$sql = "select distinct(num_iid) from ".$this->table_pre."shopping_log where createdAt > '2017-01-02 14:09:17' and num_iid > 0";
			
			return db_query_col($sql,$this->db,array(),$this->pdo);
		}

		//获取商品转化率
		public function fetchGoodsConverRate($num_iid=array()){

			$num_iid_con = "";

			if(is_array($num_iid))$num_iid_con = " and num_iid in (".implode(",",$num_iid).")";

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

			//if($r!==false)return $this->fetchGoodScore($num_iid);

	        return $r;

		}

		public function fetchGoodScore($num_iid){

			$sql = "select score,is_sold,num_iid from ".$this->table_pre."goods_info where num_iid in ($num_iid)"; 
			//echo $sql;exit;
			return db_query_row($sql,$this->db,array(),$this->pdo);

		}

		//更新商品城热卖状态
		public function updateGoodsIsSold($num_iid,$is_sold_status){

			$sql = "update ".$this->table_pre."goods_info set is_sold = $is_sold_status where num_iid in ($num_iid)"; 
			//echo $sql;exit;
			$r = db_execute($sql,$this->db,array(),$this->pdo);

		}


	}
