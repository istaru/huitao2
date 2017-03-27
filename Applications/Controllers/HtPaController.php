<?php
date_default_timezone_set('Asia/Shanghai');
header( 'Content-Type:text/html;charset=utf-8 ');
class HtPaController extends  Controller
{
    public function insertdb(){
        $data=$this->getData();
        foreach ($data as $k=>$v){
            //如果存在这条记录，就把这条记录的top值改为1
           if(M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->select()){
            M("goods")->where(['num_iid' => ['=',$v['num_iid']]])->save(['top'=>'1']);
           }else{
              M("goods")->add($v);
               //  M("goods_coupon")->add($v);  //该方法会过滤掉特殊字符&
    //           $pdo->exec('insert gw_goods(num_iid) values ("1010101010")');
            try{
                $pdo=new PDO('mysql:host=localhost;dbname=laitin','root','123456');
               // $pdo=new PDO('mysql:host=taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com;dbname=huitao','huitao','huitao909886');
                $sql='INSERT gw_goods_coupon(num_iid,coupon_id,sum,num,limited,reduce,end_time,url,coupon_url,created_date) values(?,?,?,?,?,?,?,?,?,?)';
                $stmt=$pdo->prepare($sql);
                $stmt->bindParam(1,$v['num_iid']);
                $stmt->bindParam(2,$v['coupon_id']);
                $stmt->bindParam(3,$v['sum']);
                $stmt->bindParam(4,$v['num']);
                $stmt->bindParam(5,$v['limited']);
                $stmt->bindParam(6,$v['reduce']);
                $stmt->bindParam(7,$v['end_time']);
                $stmt->bindParam(8,$v['url']);
                $stmt->bindParam(9,$v['coupon_url']);
                $stmt->bindParam(10,$v['created_date']);
                $stmt->execute();}
            catch (PDOException  $e){
                echo $e->getMessage();
            }
           };

        }
    }    //获取需要插入的数据
public function getData()
    {
        $url = "http://www.dataoke.com/top_all";
        $db_arr = [];
        $page_1 = $this->curl_get($url);
        $arr = $this->explain($page_1);
        for ($i = 0; $i < 20; $i++) {
            $db_arr[$i] = [];
            $db_arr[$i]['num_iid'] = $this->getItems($arr[$i])['num_iid'];
            $db_arr[$i]['pict_url'] = $this->getItems($arr[$i])['pict_url'];
            $db_arr[$i]['title'] = $this->getItems($arr[$i])['title'];
            $db_arr[$i]['rating'] = $this->getItems($arr[$i])['rating'];
            $db_arr[$i]['store_type'] = $this->getItems($arr[$i])['store_type'];
            $second_page = $this->getSecond($this->getItems($arr[$i])['link']);
            $arr_second = $this->explain2($second_page);
            $db_arr[$i]['reduce'] = $arr_second['reduce'];
            $db_arr[$i]['price'] = $arr_second['price'];
            $db_arr[$i]['num'] = $arr_second['num'];
            $db_arr[$i]['sum'] = $arr_second['sum'];
            $db_arr[$i]['limited'] = $arr_second['limited'];
            $db_arr[$i]['volume'] = $arr_second['volume'];
            $db_arr[$i]['item_url'] = $arr_second['item_url'];
            $db_arr[$i]['seller_id'] = $arr_second['seller_id'];
            $db_arr[$i]['coupon_url'] = $arr_second['coupon_url'];
            $db_arr[$i]['url'] = $arr_second['url'];
            $db_arr[$i]['coupon_id'] = $arr_second['coupon_id'];
            $db_arr[$i]['end_time'] = $arr_second['end_time'];
            $db_arr[$i]['category']='热卖爆款';
            $db_arr[$i]['created_date']=date("Y-m-d");
            $db_arr[$i]['top']='1';
        }
        //排除值为-1的数据元素
        for($j=0;$j<20;$j++){
            foreach ($db_arr[$j] as $k=>$v){
                if($v==-1){
                    echo $v;
                    unset($db_arr[$j]);
                    break;
                }
            }
        }
       // D(array_values($db_arr));
       return  $data=array_values($db_arr);
    }
    //获取页面-->转换成Html的字符串
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
    //解析页面
    public function explain($str){
        //去除所有换行，空格
        $res= str_replace(array("\r\n", "\r", "\n",' '), "", $str);
        //分割商品的条数，取前20条,从下标为1的开始取
        $result=preg_split('/data_goodsid=/', $res);
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
        //获取num_iid
        $n=preg_match('/[0-9]+/',$str,$match);
        if($n){
            $arr['num_iid']=$match[0];
        }else{
            $arr['num_iid']=-1;
        }


        //获取需要递归爬取的链接：优惠券面值，优惠券剩余数量，价格，商品详情，卖家id，卖家姓名，优惠券id，开始时间，结束时间
        $m=preg_match('/item.{1}id=[0-9]+/', $str, $match_link);
        if($m){
            $arr['link']="http://www.dataoke.com/".$match_link[0];
        }else{
            $arr['link']=-1;
        }

        //获取商品的主图-与淘宝只是域名不同
        $o=preg_match('/https?:\/\/[i|g].+[0-9]{2,3}.+[0-9]{2,3}\.jpg/', $str, $match_url);
        if($o){
            $arr['pict_url']=preg_split('/\_[0-9]+/',$match_url[0])[0];
        } else{
            $arr['pict_url']=-1;
        }

        //获取title
        $mystr=preg_split('/\<\/a\>\<\/span\>\<divclass\=\"goods\-slider\"title\=/', $str)[0]; //获取分割后的字符串xxxx>title
        if($mystr){
            $arr['title']=substr($mystr,strrpos($mystr, '>')+1);//从最后一个'>'开始截取，截取到最后
        }else{
            $arr['title']=-1;
        }


        //获取佣金比
        $p=preg_match('/[0-9|\.]+\<b\>\%<\/b>/', $str, $match_rate);
        if($p){
            $arr['rating']=substr($match_rate[0],0,-8);
        }else{
            $arr['rating']=-1;
        }

        //获取商品的平台，0是天猫，1是淘宝
        if(strpos($str,"tag-tmall")){
            $arr['store_type']="'0'";
        }else{
            $arr['store_type']=1;
        }
//        D($arr);
        return $arr;

    }
    //获取第二层递归的信息:优惠券面值，售价，优惠券数量和剩余，商品详情，优惠券链接，卖家信息
    function getSecond($link){
        $res=$this->curl_get($link);
        return $en_contents=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
    }
    function explain2($str){
        $str= str_replace(array("\r\n", "\r", "\n",' '), "", $str);
        $arr=[];
        //获取优惠券面值
        if(preg_match('/[0-9|\.]+元优惠券/', $str, $match_reduce)){
            preg_match('/[0-9|\.]+/',$match_reduce[0],$reduce);
            $arr['reduce']=$reduce[0];
        }else if(preg_match('/优惠券<.+\>[0-9|\.]+\</', $str, $match_reduce)){
            $arr['reduce']=preg_split('/\>|\</',$match_reduce[0])[6];
        }else{
            //表示没有匹配到
            $arr['reduce']=-1;
        };



        //获取售价
        $f=preg_match('/原价<.{0,38}[0-9|.]+/', $str, $match_price);
        if($f){
            $arr['price']=substr($match_price[0],44);
        }else{
            $arr['price']=-1;
        }


        //获取优惠券总量
        $b=preg_match('/6b6b6.{0,3}[0-9]+.{0,20}[0-9]+/', $str, $match_num_str);
        if($b){
            $arr['sum']=preg_split('/\//',$match_num_str[0])[2];
        }
        else{
            $arr['sum']=-1;
        }

        //获取已用优惠券数量,计算得出剩余优惠券数量
        $s=preg_match('/6b6b6.{0,3}[0-9]+/', $str, $match_sita);
        if($s){
            $sita=substr($match_sita[0],8);
            $arr['num']=$arr['sum']-$sita;
        }else{
            $arr['num']=-1;
        }

        //获取优惠券限额，limited,新版的大淘客上没有备注满多少，减多少，所以这里都设置为0
            $arr['limited']=0;


        //获取销量  volume
        $aa=preg_match('/销量<.+[0-9]+\</', $str, $match_volume);;
        if($aa){
            $volume=preg_split('/\>|\</',$match_volume[0])[2];
            preg_match('/[0-9|\.]+/', $volume, $match_volume);
            $arr['volume']=$match_volume[0];
            if($arr['volume']<1000){
                $arr['volume']= $arr['volume']*10000;
            }
        }else{
            $arr['volume']=-1;
        }


        //获取商品的链接
        $y=preg_match('/https:\/\/[(detai)|(item)][\w|.|\/|\?|\=]+/', $str, $match_item);
        if($y){
            $arr['item_url']=$match_item[0];
        }
        else{
            $arr['item_url']=-1;
        }

        //获取优惠券链接
        if(preg_match('/https?:\/\/shop.m[\w|.|\/|\?|\&|\=]+/', $str, $match_link3)){
            $coupon_link=$match_link3[0];
            //获取卖家id
            $arr['seller_id']=preg_split('/\=|\&/',$coupon_link)[1];
            //获取优惠券id
            $arr['coupon_id']=preg_split('/\=|\&/',$coupon_link)[3];
            //存入数据库的优惠券链接
            $arr['coupon_url']="https://h5.m.taobao.com/ump/coupon/detail/index.html?sellerId=".$arr['seller_id']."&activityId=".$arr['coupon_id']."&global_seller=false&currency=CNY";
            $arr['url']="https://taoquan.taobao.com/coupon/unify_apply.htm?sellerId=".$arr['seller_id']."&activityId=".$arr['coupon_id'];

        }
        else{
            $arr['seller_id']=-1;
            $arr['coupon_id']=-1;
            $arr['coupon_url']=-1;
        }

        //获取优惠券结束时间，新版的淘客助手上没有时间的提示，这里统一设置为当前时间多3天
        if(preg_match('/20[0-9]+\/[0-9]{1,2}\/[0-9]{1,2}/', $str, $match_end)){
            $arr['end_time']=date("Y-m-d",strtotime($match_end[0]));
        }else{
            $arr['end_time']=date("Y-m-d",strtotime('+3days'));
        };
//        D($arr);
        return $arr;
    }

}