<?php
class ShopController extends AppController
{
    //http://localhost/shopping/shop/shopResult
    public function shopResult()
    {
        file_put_contents(DIR.'/runtime/logs/'.time().'.txt', file_get_contents('php://input'));
        echo 'ok';
    }
    //热更新 ios 安卓
    public function android() {
        ob_clean();
        !empty($_GET['name']) or info('缺少文件名称');
        $filepath = '../android/'.$_GET['name'];
        $fp=fopen($filepath,"r");
        $file_Size = filesize($filepath);
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".$file_Size);
        header("Content-Disposition: attachment; filename=".$_GET['name']);
        echo fread($fp, $file_Size);
        fclose($fp);
    }
}