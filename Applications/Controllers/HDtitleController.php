<?php
class HDtitleController  extends  Controller
{
    public function updategoods(){
        /**
         * 获取上次补全到哪里的指针,如果当日有新增，只补全当日，如果当日没有新增，向下补全之前的商品短连接
         */

        $ordersql="select max(id) m_id from gw_goods_online  where createdAt>'".date("Y-m-d")."' and dtitle is not null GROUP BY num_iid order by m_id asc limit 1";
        $order=M()->query($ordersql,'single');
        if($order['m_id']){
            echo $order['m_id'];
            $where="where id<".$order['m_id'];
        }else{
            //$order['m_id']为false有两种情况，一种是今日还没有新增数据，一种是新增的数据没有进行过补全操作
            //查询今日有无新增数据,无新增，补全之前的，有新增，开始从头补全
            $flag=M()->query("select count(*) sum  from gw_goods_online where createdAt>'".date("Y-m-d")."'",'single');
             if($flag['sum']){
                 $where="";
             }else{
                 $ordersql2="select max(id) m_id from gw_goods_online  where  dtitle is not null GROUP BY num_iid order by m_id asc limit 1";
                 $order=M()->query($ordersql2,'single');
                 $where="where id<".$order['m_id'];
             };

        }
        /**
         * 一次性补全的个数,不能过小，过小导致间距较小，跳不过那些难以补全的信息
         */
    for($i=0;$i<100;$i++){
        $sql="select num_iid from gw_goods_online ".$where."  ORDER  BY id DESC limit ".($i+1).",1";
//        D($sql);
        $res=M()->query($sql,'single');
//        D($res['num_iid']);
        $getdata=file_get_contents("http://api.dataoke.com/index.php?r=port/index&appkey=ar6h3wb99l&v=2&id=".$res['num_iid']);
        $arr=json_decode($getdata,true);
        $dtitle=$arr['result']['D_title'];
        if($dtitle!=null&&$dtitle!=''){
            $sql="update gw_goods_online set dtitle='".$dtitle."' where num_iid='".$res['num_iid']."'";
//           D($sql);
            M()->exec($sql);
        }

    }
}

}