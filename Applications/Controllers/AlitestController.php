<?php
include DIR_LIB.'taobaosdk/TopSdk.php';
class AlitestController
{
//    public static $c  = null;
//    public function __construct() {
//        self:: $c            = new TopClient("23630277","a13d3d6a8cf33d063f630f3d2b571727");
//        self:: $c->format    = 'json';
//    }
//    function  getGooditem(){
//        $num_iid='545870753682';
//        $req = new TbkItemInfoGetRequest;
//        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url");
//        $req->setPlatform("1");
//        $resp=self::$c ->execute($req);
//        $req->setNumIids($num_iid);
//        var_dump($resp) ;
//    }
      public function getdata(){
          $json_ret = file_get_contents("http://api.dataoke.com/index.php?r=Port/index&type=paoliang&appkey=ar6h3wb99l&v=2");
          D($json_ret);
      }

}