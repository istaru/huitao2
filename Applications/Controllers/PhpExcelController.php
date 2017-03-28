<?php
include DIR_LIB . 'PHPExcel/Classes/PHPExcel.php';
// phpexcel 工具类
class PhpExcelController {
    public  $phpExcel;
    public function __construct() {
        $this->phpExcel = new PHPExcel;
    }
    //设置header头
    public function setHeader($fileName = '下载') {
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$fileName.'.xlsx');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    }

}