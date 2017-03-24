<?php

   class InterfaceController extends Controller{

    function curl_req1($url, $post_data=null,$info=null){
        //初始化一个 cURL 对象
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,4);
        if($post_data){
            // 设置请求为post类型
            curl_setopt($ch, CURLOPT_POST, 1);
            // 添加post数据到请求中
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        if(strpos($url,"https")>=0){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在

        }
        $rsp = array();
        // 执行post请求，获得回复
        $content = curl_exec($ch);

        $infos = curl_getinfo($ch);

        if($info){
            if(!is_array($info))$info = array($info);
            foreach ($info as $k => $v) {

                    $response[$v] = $infos[$v];

            }
            $response["content"] = $content;


        }else $response = $content;

        curl_close($ch);

        return $response;
    }

    //每日的商品排列，导入后触发
    function index(){
       
        $goods = new GoodsController;
        $rt = $goods->index();
       //print_r($rt);exit;
        //$r = json_decode($rt);
       //
        if($rt->status==1){
            $filter = new FilterConfigController;
            $r = $filter->sort();
            if($r->status==1)  {
                //清空内存依赖
                A('Goods:delAllGoods');
            } 
            return $r;
                  
        }else{
            return $rt;
            
        }
    }

    //虚拟点击事件
    function testClick(){
        $record = new RecordController;
        $record->clickRecord();
    }
    //定时存click到数据库
    function loopClick(){
        $record = new RecordController;
        $record->loopDailyUidGoodsReport();
    }

    function loopRecord(){
        $record = new RecordController;
        $record->dailyReportRecord();
    }
    //昨天最后数据的残余处理
    function restRecord(){
        $date = date("Y-m-d",strtotime("-1 day"));
        //$date = "2017-01-11";
        $record = new RecordController;
        $record->inputDailyUidGoodsReport($date);
        $record->dailyReportRecord($date);
    }
    //手动导入
    function inputClick(){
        $date = "2017-01-08";
        $record = new RecordController;
        $record->inputDailyUidGoodsReport($date);
  
    }
    function inputRecord(){
        $date = "2017-01-08";
        $record = new RecordController;

        $record->dailyReportRecord($date);
    }     //
    function testTBApi(){

        $json = '{
            "tmc_message": [
                {
                    "content": "{\"buyer_id\":\"AAHnANc-ADye1K1g5A-7nMSJ\",\"extre\":\"isv_code:appisvcode;\",\"paid_fee\":\"8.60\",\"shop_title\":\"景如工艺品工厂店\",\"is_eticket\":false,\"create_order_time\":\"2016-12-28 13:14:46\",\"order_id\":\"2942606005233222\",\"order_status\":\"4\",\"seller_nick\":\"支架底座炭雕超市\",\"auction_infos\":[{\"detail_order_id\":\"2942606005233222\",\"auction_id\":\"AAHLANc9ADye1K1g5HSefFqw\",\"real_pay\":\"8.60\",\"auction_pict_url\":\"i2/1998744183/TB26J48acwb61Bjy0FfXXXvlpXa_!!1998744183.jpg\",\"auction_title\":\"盘子支架木质普洱茶饼陶瓷圆盘架仿红木底座托架工艺品摆件架摆盘\",\"auction_amount\":\"1\"}]}",
                    "id": 4130201825909792000,
                    "pub_app_key": "12497914",
                    "pub_time": "2016-12-28 13:24:26",
                    "topic": "taobao_tae_BaichuanTradePaidDone"
                }
            ]
        }';
        $_POST['message'] = json_decode($json,true);

        //print_r($_POST);

       // $url = "es1.laizhuan.com/shopping/TaoBaoKe/run?name=message";
       // $post_data = $_POST;
        $_GET['name'] = "message";
        $tbk = new TaoBaoKeController;
        $tbk->run();
       // print_r($post_data);
       // $this->curl_req1($url, $post_data);

    }

    function test(){

    $host = "http://es1.laizhuan.com";
    
    //echo 234;
    $param_arr = getopt('url:');
    //var_dump($param_arr);
    $url = $param_arr["url"]?getopt('url:'):$_GET["url"];

    switch ($url) {
        //测点击
        case 'clickrecord':
            $param = $host."/shopping/record/clickRecord?did=1&abc=sdf&uid=sdlfjsef".rand(0,9)."&fee=34&order_id=2407625183347946&order_status=6001&num_iid=54236341230".rand(0,9);
            $url = $param."?url=".$url;
            //echo $url;exit;
            //curl_req($url);
            $params["num_iid"] = "54236341230".rand(0,9);
            $params["uid"] = "sdlfjsef".rand(0,9);
            $_REQUEST = $params;
            //print_r($_REQUEST);//exit;
            $this->testClick();
            break;
        
        case 'goods':
            $url = $host."/shopping/index.php?c=Goods&f=index";


        break;

        default:
            # code...
            break;
        }
    /*
    function clickrecordInterface(){

         $url = "http://ec2-54-199-233-186.ap-northeast-1.compute.amazonaws.com/shopping/record/clickRecord?did=1&abc=sdf&uid=sdlfjsef".rand(0,9)."&fee=34&order_id=2407625183347946&order_status=6001&num_iid=54236341230".rand(0,9);

         curl_req($url);
    }
*/
    }

}

?>