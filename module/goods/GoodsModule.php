<?php

define("GW_",0);
//需要被计算的商品状态
//有效，手工下架
define("GW_SCORED_GOODS",array(1,5));
//选品库的商品状态
//有效，手工下架
define("GW_FAV_GOODS",array(1,5));
//下架
define("GW_OFF_STS",2);
//每小时最多增加评分
define("GW_HOUR_ADD_SCORE_LIMIT",4);

define("TALBE_PRE","gw_");

class GoodsModule extends Module{

    public $date;

    public $isDebug = 1;

    private $module = "goods";

    protected $db;

    protected $pdo;

    public function __construct(){

        parent::__construct();

        $this->__preAction();

        $this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

        $this->db = $this->isDebug?"shopping_new":"huitao";

        //$this->date = isset($_GET["date"])&&!empty($_GET["date"])?$_GET["date"]:date("Y-m-d");
        if($this->isDebug){

            echo $this->date." : ".__CLASS__." loaded.<br>";
        }

    }
    //开始行为前的预定义动作
    protected function __preAction(){

        if(substr(php_sapi_name(), 0, 3) == 'cli'){

            $arr = getopt('d:');

            $this->date = isset($arr['d']) ? $arr['d'] : date("Y-m-d");

        }else{

            $this->date = isset($_REQUEST["date"]) ? $_REQUEST["date"] : date("Y-m-d");
        }

    }

    //
    public function tools(){



    }
}



   