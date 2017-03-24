<?php
class HtImportlogController extends  Controller
{
    //查出今日新增多少
    public function querylog(){
        isset($_REQUEST['date'])&&$_REQUEST['date']!=null?$date=$_REQUEST['date']:$date=date('Y-m-d');
        $sql='select  (select count(DISTINCT num_iid) from gw_goods where created_date='."'$date'".' and top="1") as hot,
               (select count(*) from gw_goods_online where createdAt>'."'$date'".' and status="1")as online,
              count(*) as sum from gw_goods where created_date='."'$date'";
//        D($sql);
        $data=M()->query($sql);
        $data['online']>0?$msg=date("Y-m-d H:i:s").'，导入商品成功,共导入:'.$data['sum'].'条，其中导入热卖:'.$data['hot'].'，选品库新增:'.$data['online']
                          :$msg=date("Y-m-d H:i:s").'导入商品表失败，请检查程序';
        echo $msg;
    // 15 12 * * * /usr/bin/curl http://180.76.160.251/shopping/HtImportlog/querylog  >> /tmp/import.log;
         //商品导入表示，如果成功888，如果失败发119
         $data['online']>0?$staus='888' :$staus='119';
         $code=new VcodeController();
         $code->sendCode("18221924339",$staus);
    }

}