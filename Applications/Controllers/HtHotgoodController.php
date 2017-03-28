<?php
date_default_timezone_set('Asia/Shanghai');
class HtHotgoodController extends  Controller
{
    public  function import(){
        $data=$this->dataokedata();
        foreach ($data as $k=>$v){
            //如果存在这条记录，就把这条记录的top值改为1
            if(M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->select()){
                M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->save(['top'=>'1']);
            }else{
                M("goods")->add($v);
                //  M("goods_coupon")->add($v);  //该方法会过滤掉特殊字符&
                try{
//                    $pdo=new PDO('mysql:host=localhost;dbname=laitin','root','123456');
                     $pdo=new PDO('mysql:host=taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com;dbname=huitao','huitao','huitao909886');
                    $sql='INSERT gw_goods_coupon(num_iid,coupon_id,sum,num,reduce,end_time,url,coupon_url,created_date) values(?,?,?,?,?,?,?,?,?)';
                    $stmt=$pdo->prepare($sql);
                    $stmt->bindParam(1,$v['num_iid']);
                    $stmt->bindParam(2,$v['coupon_id']);
                    $stmt->bindParam(3,$v['sum']);
                    $stmt->bindParam(4,$v['num']);
                    $stmt->bindParam(5,$v['reduce']);
                    $stmt->bindParam(6,$v['end_time']);
                    $stmt->bindParam(7,$v['url']);
                    $stmt->bindParam(8,$v['coupon_url']);
                    $stmt->bindParam(9,$v['created_date']);
                    $stmt->execute();}
                catch (PDOException  $e){
                    echo $e->getMessage();
                }
            };

        }
    }
    public function dataokedata(){
        $mydata=[];
        $getdata=file_get_contents("http://api.dataoke.com/index.php?r=Port/index&type=top100&appkey=ar6h3wb99l&v=2");
        $res_arr=json_decode($getdata,true)['result'];
        for($i=0;$i<50;$i++){
            $mydata[$i]['num_iid']=$res_arr[$i]['GoodsID'];  /*商品id*/
            $mydata[$i]['title']=$res_arr[$i]['Title'];   /*标题*/
            $mydata[$i]['pict_url']=$res_arr[$i]['Pic'];   /*图片地址*/
            $mydata[$i]['price']=$res_arr[$i]['Org_Price'];   /*原价*/
            $mydata[$i]['store_type']=$this->getStoreType($res_arr[$i]['IsTmall']);  /*是否天猫*/
            $mydata[$i]['volume']=$res_arr[$i]['Sales_num'];  /*销量*/
            $mydata[$i]['seller_id']=$res_arr[$i]['SellerID'];   /*卖家id*/
            $mydata[$i]['rating']=$res_arr[$i]['Commission'];   /*佣金比*/
            $mydata[$i]['coupon_id']=$res_arr[$i]['Quan_id'];   /*优惠券id*/
            $mydata[$i]['reduce']=$res_arr[$i]['Quan_price'];   /*优惠券金额*/
            $mydata[$i]['end_time']=date('Y-m-d', strtotime($res_arr[$i]['Quan_time']));   /*优惠券的结束时间*/
            $mydata[$i]['num']=$res_arr[$i]['Quan_surplus'];   /*优惠券剩余数量*/
            $mydata[$i]['created_date']=date("Y-m-d");   /*创建时间*/
            $mydata[$i]['top']="1";   /*是否热卖*/
            $mydata[$i]['sum']=$res_arr[$i]['Quan_receive']+$res_arr[$i]['Quan_surplus'];   /*总量=已领数量+剩余数量*/
            $mydata[$i]['url']="https://taoquan.taobao.com/coupon/unify_apply.htm?sellerId=".$res_arr[$i]['SellerID']."&activityId=".$res_arr[$i]['Quan_id'];   /*优惠券链接*/
            $mydata[$i]['coupon_url']="https://h5.m.taobao.com/ump/coupon/detail/index.html?sellerId=".$res_arr[$i]['SellerID']."&activityId=".$res_arr[$i]['Quan_id']."&global_seller=false&currency=CNY";   /*手机券链接*/
        }
//        D($mydata);
        return  $mydata;
    }


    //获取isTmall 1天猫--》入库0 为天猫
    public function getStoreType($str)
    {
        return $str === "1" ? 0 : 1;
    }

}