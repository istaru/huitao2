<?php
//计算购买的订单装填
define("PUR_ORDER_STS",array(2,5));


define("TALBE_PRE","ngw_");

class RecordModule extends Module{

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



   