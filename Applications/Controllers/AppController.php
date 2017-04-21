<?php
/*
app交互公共控制器
 */
class AppController extends Controller
{
    const PRE = 'ngw_';
	const DUIBA_AUTO_URL = 'http://www.duiba.com.cn/autoLogin/autologin?';
	const DUIBA_KEY = '3JaYVqyA2yXdvTKD14ybisvjzcT9';
	const DUIBA_SECRET = 'FTfdo5BFrq9Svto1KKb3Lzdsmvo';
    // const DUIBA_KEY = '4CgXMXbZYifpSWv1wHszsN9UWr2z';
    // const DUIBA_SECRET = '4MD6bsEmMoSNTzBA2RPi5oQH7AT7';
    const ALIDAYU_KEY = '23559394';
    const ALIDAYU_SECRET = '14ec3bb9c8d206eb00c97241cff58f60';
    const PERCENT = 0.7;
    const SHARE_URL = 'http://terui.net.cn/shopping_new/Applications/Views/bg_gw/share/share.html?num_iid=';
    //http://liaoshiwei.cn/share/share.html
    static $aes = null;
    // static $aes = true;
    protected $param;
    public $status = 1;
    public function __construct()
    {
        // echo rawurldecode(file_get_contents('php://input'));die;
        if($_SERVER['REQUEST_METHOD'] == 'POST')
            $this->dparam = json_decode(rawurldecode(file_get_contents('php://input')),true);
        else
            $this->dparam = $_GET;

        //判断接收的数据是否AES加密
        if(!empty($this->dparam['secret'])){
            $this->dparam = aes_decode($this->dparam['content'],$this->dparam['secret']);
            self::$aes = true;
        }
        //status 等1 的情况下 才会去过滤
        if($this ->status == 1 && !empty($this->dparam) )
            $this ->dparam = $this->filter_field($this->dparam);
    }

    //过滤非表字段的数据
    public function filter_field($arr=[],$tmp=[]){
        foreach ($arr as $k => &$v)
            if($v == '0' || !empty($v)) $tmp[$k] = $v;

        return $tmp;
    }

}


