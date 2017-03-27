<?php
date_default_timezone_set('Asia/Shanghai');
header( 'Content-Type:text/html;charset=utf-8 ');
class HtPa2Controller extends  Controller {
    public function insert2db(){
        $data=$this->getdata();
//         print_r($data);
        foreach ($data as $k=>$v){
            //如果存在这条记录，就把这条记录的top值改为1
            if(M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->select()){
                M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->save(['top'=>'1']);
            }
//             else{
//                 M("goods")->add($v);
//                 try{
//                     $pdo=new PDO('mysql:host=localhost;dbname=laitin','root','123456');
//                     //$pdo=new PDO('mysql:host=taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com;dbname=huitao','huitao','huitao909886');
//                     $sql='INSERT gw_goods_coupon(num_iid,coupon_id,sum,num,limited,reduce,end_time,url,coupon_url,created_date) values(?,?,?,?,?,?,?,?,?,?)';
//                     $stmt=$pdo->prepare($sql);
//                     $stmt->bindParam(1,$v['num_iid']);
//                     $stmt->bindParam(2,$v['coupon_id']);
//                     $stmt->bindParam(3,$v['sum']);
//                     $stmt->bindParam(4,$v['num']);
//                     $stmt->bindParam(5,$v['limited']);
//                     $stmt->bindParam(6,$v['reduce']);
//                     $stmt->bindParam(7,$v['end_time']);
//                     $stmt->bindParam(8,$v['url']);
//                     $stmt->bindParam(9,$v['coupon_url']);
//                     $stmt->bindParam(10,$v['created_date']);
//                     $stmt->execute();}
//                 catch (PDOException  $e){
//                     echo $e->getMessage();
//                 }
//             };

        }

    }
    public function getdata(){
        $url="http://www.taokezhushou.com/top100";
        $str_p1= $this->curl_get($url);
        $arr=$this->explain($str_p1);
        $data=[];
        for($i=0;$i<20;$i++){
            $data_1=$this->getItems($arr[$i]);
            $data[$i]=[];
            $data[$i]['pict_url']=$data_1['pict_url'];
            $data[$i]['sum']=$data_1['sum'];
            $data[$i]['num']=$data_1['num'];
            $data[$i]['rating']=$data_1['rating'];
            $data[$i]['category']='热卖爆款';
            $data[$i]['top']='1';
            $data[$i]['created_date']=date("Y-m-d");
            $str_page2=$this->getSecond($data_1['page2']);
            $arr_second=$this->explain2($str_page2);
            /**第二层页面的数据,这个加判断是要过滤商品过期导致页面无法访问的情况*/
            if($arr_second){
                $data[$i]['store_type']=$arr_second['store_type'];
                $data[$i]['num_iid']=$arr_second['num_iid'];
                $data[$i]['title']=$arr_second['title'];
                $data[$i]['item_url']=$arr_second['item_url'];
                $data[$i]['price']=$arr_second['price'];
                $data[$i]['volume']=$arr_second['volume'];
                $data[$i]['seller_id']=$arr_second['seller_id'];
                $data[$i]['coupon_id']=$arr_second['coupon_id'];
                $data[$i]['reduce']=$arr_second['reduce'];
                $data[$i]['limited']=$arr_second['limited'];
                $data[$i]['end_time']=$arr_second['end_time'];
                $data[$i]['url']=$arr_second['url'];
                $data[$i]['coupon_url']=$arr_second['coupon_url'];
            }

        }
        //排除值为-1的数据元素
        for($j=0;$j<20;$j++){
            foreach ($data[$j] as $k=>$v){
                if($v==-1){
//                echo $v;
                    unset($data[$j]);
                    break;
                }
            }
        }
        // D(array_values($data));
        return  $mydata=array_values($data);
    }






    //获取一级页面-->转换成Html的字符串
    public function curl_get($url){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output=curl_exec($ch);
        curl_close($ch);
        if($output){
            return $output;

        }else{
            info("爬取页面失败",-1);
        }
    }
    //解析第一层页面
    public function explain($str){
        //去除所有换行，空格
        $res= str_replace(array("\r\n", "\r", "\n",' '), "", $str);
        //分割商品的条数，取前20条,从下标为1的开始取
        $result=preg_split('/good1_onefl/', $res);
        $myarr=array();
        if($result){
            for($i=0;$i<20;$i++){
                $myarr[$i]=$result[$i+1];
            }
            return $myarr;
        }
        else{
            info("解析第一层页面失败",-1);
        }
    }
    //解析第一层每条记录，每条记录是一个字符串
    function getItems($str){
        $arr=[];
        /**获取商品主图*/
        $a=preg_match('/http:\/\/acdn\.taokezhushou.com\/i[^@]+/', $str, $match_purl);
        if($a){
            $arr['pict_url']=$match_purl[0];
        }else{
            $arr['pict_url']=-1;
        }
        /**获取优惠劵剩余数量*/
        $b=preg_match('/(?=num2gd_wd1")[^\<]+/', $str, $match_num);
        if($b){
            $arr['num']=substr($match_num[0],12);
        }else{
            $arr['num']=-1;
        }
        /**获取优惠劵总数*/
        $c=preg_match('/(?=num3")[^\<]+/', $str, $match_sum);
        if($c){
            $arr['sum']=substr($match_sum[0],6);
        }else{
            $arr['sum']=-1;
        }
        /**获取佣金比*/
        $d=preg_match('/[\d|.]+%/', $str, $match_ratings);
        if($d){
            preg_match('/[\d|.]+/', $match_ratings[0], $match_rating);
            $arr['rating']=$match_rating[0];
        }else{
            $arr['rating']=-1;
        }
        /**获取需要递归的网址*/
        $e=preg_match('/(?=detail)detail\/\d+/', $str, $match_2page);
        if($e){
            $arr['page2']="http://www.taokezhushou.com/".$match_2page[0];
        }
//        D($arr);
        return $arr;
    }
    //获取第二层递归的信息
    function getSecond($link){
        $res=$this->curl_get($link);
        return $res;
    }
    //解析第二层页面
    function explain2($str){
        $str1= str_replace(array("\r\n", "\r", "\n",' '), "", $str);
        //获取有用的字符串
        $str=preg_split('/wthclearfix/',$str1)[1];
        $arr=[];
        if($str) {
//        echo $str;
            /**获取平台类型，天猫为0，淘宝为1*/
            if (strpos($str, "tit1")) {
                $arr['store_type'] = "'0'";
            } else if (strpos($str, "tit0")) {
                $arr['store_type'] = 1;
            } else {
                $arr['store_type'] = -1;
            }

            /**获取商品的num_iid*/
            $f = preg_match('/[0-9]{8,}/', $str, $match_num_iid);
            if ($f) {
                $arr['num_iid'] = $match_num_iid[0];
            } else {
                $arr['num_iid'] = -1;
            }

            /**获取商品的title*/
            $g = preg_match('/h3[^\<]+/', $str, $match_title);
            if ($g) {
                $arr['title'] = substr($match_title[0],3);
            } else {
                $arr['title'] = -1;
            }
            /**获取商品详情链接*/
            if($arr['store_type']==1){
                $arr['item_url']="https://item.taobao.com/item.htm?id=".$arr['num_iid'];
            }else{
                $arr['item_url']="https://detail.tmall.com/item.htm?id=".$arr['num_iid'];
            }
            /**获取商品的price*/
            $h = preg_match('/在售价[^\<]+/', $str, $match_price);
            if ($h) {
                $arr['price'] = substr($match_price[0],26);
            } else {
                $arr['price'] = -1;
            }
            /**获取商品当前的销量*/
            $i = preg_match('/目前销量[^\<]+/', $str, $match_volume);
            if ($i) {
                $arr['volume'] = substr($match_volume[0],15);
            } else {
                $arr['volume'] = -1;
            }
            /**获取卖家id*/
            $j = preg_match('/seller_id=[0-9]+/', $str, $match_seller_id);
            if ($j) {
                $arr['seller_id'] = substr($match_seller_id[0],10);
            } else {
                $arr['seller_id'] = -1;
            }
            /**获取优惠劵id*/
            $l= preg_match('/activity_id=\w+/', $str, $match_coupon_id);
            if ($l) {
                $arr['coupon_id'] = substr($match_coupon_id[0],12);
            } else {
                $arr['coupon_id'] = -1;
            }
            /**获取优惠劵的limited*/
            $m= preg_match('/单笔满[0-9|\.]+/', $str, $match_limited);
            if ($m) {
                $arr['limited'] = substr($match_limited[0],9);
            } else {
                $arr['limited'] = "'0'";
            }
            /**获取优惠劵的reduce*/
            $m= preg_match('/优惠券\&.{5,20}span\>[0-9|\.]+/', $str, $match_reduce);
            if ($m) {
                $arr['reduce'] = substr($match_reduce[0],21);
            } else {
                $arr['reduce'] = -1;
            }
            /**获取优惠劵的结束时间*/
            $n=preg_match('/20[0-9]+\/[0-9]{1,2}\/[0-9]{1,2}/', $str, $match_end);
            if($n){
                $arr['end_time']=date("Y-m-d",strtotime($match_end[0]));
            }else{
                $arr['end_time']=-1;
            };
            /**获取PC端的优惠劵链接*/
            $arr['url']="https://taoquan.taobao.com/coupon/unify_apply.htm?sellerId=".$arr['seller_id']."&activityId=".$arr['coupon_id'];

            /**获取手机端的优惠券链接*/
            $arr['coupon_url']="https://h5.m.taobao.com/ump/coupon/detail/index.html?sellerId=".$arr['seller_id']."&activityId=".$arr['coupon_id']."&global_seller=false&currency=CNY";

        }
        return $arr;

    }
}