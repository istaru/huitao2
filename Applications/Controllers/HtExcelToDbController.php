<?php
//echo 1 ;die;
date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', '-1');
class HtExcelToDbController extends Controller
{
    //添加商品信息到数据库
    public static $flag_goods=false;
    public static $flag_coupon=false;
    public $data;
    public function export(){
        if(isset($_GET['date'])){
            $mydate=$_GET['date'];
        }else{
            $mydate=date("Ymd");
        }
        $data=self::format_excel2array($mydate);
        $flag_goods=$this->addByExcel($data);
        if($flag_goods){
            $flag_coupon=$this->addcoupon($data);
            if($flag_coupon){
                //开始导入热卖,大淘客
                $dataoke=new HtHotgoodController();
                $dataoke->import();
                echo("上架筛选开始...".date("Y-m-d H:i:s")."<br/>");
                $interface=new InterfaceController();
                $res=$interface->index();
                //  var_dump($res);
                $msg=$array = (array)$res;
                if(isset($msg['status'])&&$msg['status']==1){
                    echo ("商品表列出成功").date("Y-m-d H:i:s");
                }
                else{
                    echo ("商品表列出失败").date("Y-m-d H:i:s");
                }
            }else{
                info("导入优惠券失败",'-1');
            }
        }else{
            info("导入商品表失败",'-1');
        }
    }
    public function addByExcel($data)
    {
        echo( "导入商品表开始...".date("Y-m-d H:i:s")."<br/>");
//        echo $filePath;
        for ($i = 0; $i < count($data); $i++) {
            //卸载商品主表不需要的字段，因为插入时需要完全匹配，所以必须卸载
            unset($data[$i]['sum']);
            unset($data[$i]['val']);
            unset($data[$i]['num']);
            unset($data[$i]['limited']);
            unset($data[$i]['reduce']);
            unset($data[$i]['start_time']);
            unset($data[$i]['end_time']);
            unset($data[$i]['url']);
            unset($data[$i]['coupon_url']);
        }
        //D($data);
        return  $res = A('HtExcelToDb:addGoodsbyexcle', [$data]);

    }

    //添加优惠券到数据库
    public function addcoupon($data)
    {
        echo("导入优惠券表开始...".date("Y-m-d H:i:s")."<br/>");
        for ($i = 0; $i < count($data); $i++) {
            //卸载优惠券表不需要的字段，因为插入时需要完全匹配，所以必须卸载
            unset($data[$i]['title']);
            unset($data[$i]['price']);
            unset($data[$i]['pict_url']);
            unset($data[$i]['item_url']);
            unset($data[$i]['volume']);
            unset($data[$i]['rating']);
            unset($data[$i]['seller_name']);
            unset($data[$i]['store_type']);
            unset($data[$i]['store_name']);
            unset($data[$i]['category']);
            unset($data[$i]['promotion_url']);
            unset($data[$i]['seller_id']);
            unset($data[$i]['source']);
        }
//        D($data);
        return   $res = A('HtExcelToDb:addcouponbyexcle', [$data]);

    }

    //将excle表格的内容转换成二维索引数组
    function format_excel2array($date='' ,$sheet = 0)
    {
        $filePath=DIR.'/resource/goods_excel/goods' . $date.'.xls';
        $date=date("Y-m-d",strtotime($date));
        require_once DIR_LIB . 'PHPExcel/Classes/PHPExcel.php';
        if (empty($filePath) or !file_exists($filePath)) {
            die('file not exists');
        }

        $PHPReader = new PHPExcel_Reader_Excel2007();        //建立reader对象
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);            //建立excel对象
        $currentSheet = $PHPExcel->getSheet($sheet);        //**读取excel文件中的指定工作表*/
        $allColumn = $currentSheet->getHighestColumn();        //**取得最大的列号*/
        $allRow = $currentSheet->getHighestRow();             //**取得一共有多少行*/
        $data = array();
        //通过行号获得索引数组的索引
        /**商品id，商品名，商品主图，商品详情，
         * 一级分类，淘宝客链接，商品价格，月销量，
         * 佣金比，佣金,卖家旺旺，卖家id，
         * 店铺名称，平台类型，优惠券id，优惠券总量，
         * 优惠券剩余，优惠券面额，优惠券开始时间，优惠券结束时间，优惠券链接，商品优惠券推广链接
         */
        //自定义索引
        $rowname = ['A' => 'num_iid', 'B' => 'title', 'C' => 'pict_url', 'D' => 'item_url',
            'E' => 'category', 'F' => 'promotion_url', 'G' => 'price', 'H' => 'volume',
            'I' => 'rating', 'J' => 'backmoney_w', 'K' => 'seller_name', 'L' => 'seller_id',
            'M' => 'store_name', 'N' => 'store_type', 'O' => 'coupon_id', 'P' => 'sum',
            'Q' => 'num', 'R' => 'val', 'S' => 'start_time', 'T' => 'end_time',
            'U' => 'url', 'V' => 'coupon_url_pro'
        ];
        for ($rowIndex = 2; $rowIndex <= $allRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从2开始，列从A开始
            for ($colIndex = 'A'; $colIndex <= $allColumn; $colIndex++) {
                $addr = $colIndex . $rowIndex;
                $cell = $currentSheet->getCell($addr)->getValue();
                if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
                    $cell = $cell->__toString();
                }
                $data[$rowIndex][$rowname[$colIndex]] = $cell;
            }
//            $data[$rowIndex]['title'] =self::ReplaceSpecialChar($data[$rowIndex]['title']);
//            $data[$rowIndex]['category'] =self::ReplaceSpecialChar($data[$rowIndex]['category']);
//            $data[$rowIndex]['seller_name'] =self::ReplaceSpecialChar($data[$rowIndex]['seller_name']);
//            $data[$rowIndex]['store_name'] =self::ReplaceSpecialChar($data[$rowIndex]['store_name']);
            $data[$rowIndex]['created_date'] = $date;
            $data[$rowIndex]['source'] = '0';
            $data[$rowIndex]['store_type'] = self::getStoreType($data[$rowIndex]['store_type']);
            $data[$rowIndex]['limited'] = self::getlimitStr($data[$rowIndex]['val']);
            $data[$rowIndex]['reduce'] = self::getreduceStr($data[$rowIndex]['val']);
            $data[$rowIndex]['coupon_url'] =self::getCouponUrl($data[$rowIndex]['seller_id'],$data[$rowIndex]['coupon_id']);
            //卸载两张表都不需要的字段
            unset($data[$rowIndex]['backmoney_w']);
            unset($data[$rowIndex]['coupon_url_pro']);

        }
//          D($data);
        return array_values($data);
    }

    //获取limit，即满多少减多少的满，无条件的直接返回0
    public function getlimitStr($str)
    {
        $b = preg_match_all('/\d+/', $str, $res);
        $len = count($res[0]);
        return $len == 1 ? 'xx' : $res[0][0];
    }

    //获取reduce，即满多少减多少的减
    public function getreduceStr($str)
    {
        $b = preg_match_all('/\d+/', $str, $res);
        $len = count($res[0]);
        return $len == 1 ? $res[0][0] : $res[0][1];
    }

    //获取store_type，天猫为0，淘宝为1
    public function getStoreType($str)
    {
        return $str === "天猫" ? 0 : 1;
    }
    //获取优惠券的更新的url
    public function getCouponUrl($seller_id,$coupon_id){
        return 'https://h5.m.taobao.com/ump/coupon/detail/index.html?sellerId='.
        $seller_id.'&'.'activityId='.$coupon_id.'&global_seller=false&currency=CNY';

    }
}