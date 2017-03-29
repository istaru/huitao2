<?php

class Mmodel extends Module{

    public $pdo;

    //public $db;
   
    //public $table_pre = "gw_";

    public function __construct($isDebug=1){

        parent::__construct();

        $this->isDebug = $isDebug;

        //$this->pdo = $this->isDebug?locationCon("shopping_new"):shoppingCon();

        //$this->db = $this->isDebug?"shopping_new":"huitao";

     


        if($this->isDebug){

            echo $this->date." : ".__CLASS__." loaded.<br>";
        }
       
    }

}