<?php
/**
 * 路由解析与自动加载
 */

class Route {
    public static $arr = [];
    public function __construct() {
        $time = microtime(true);
        self::loadCoreClass(include DIR_CORE.'dependentFile.php');
        Log::startTime($time);
        set_error_handler(function($code, $msg, $file, $line) {
            self::errInfo($code, $msg, $line, $file);
       });
        set_exception_handler(function($e) {
            self::errInfo($e->getCode(), $e->getMessage(), $e->getLine(), $e->getFile(), $e->getTrace());
       });
        self::pathInfo();
    }

    public static function errInfo($code, $msg, $line, $file, $getTrace = '') {
        $data = [
            'time'      => date('Y-m-d H:i:s'),
            'url'       => Log::getUrl(),
            'parameter' => empty(array_filter($_POST)) ? trim(file_get_contents('php://input')) : $_POST,
            'code'      => $code,
            'line'      => $line,
            'file'      => mb_substr($file, strrpos($file, '/') + 1),
            'message'   => $msg,
            'getTrace'  => $getTrace
        ];
        if(!APP_DEBUG) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            file_put_contents(DIR_RUNTIME_LOG.date('Ym').DS.date('d').'error.json', $data, FILE_APPEND);
        } else echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    /**
     * [pathInfo 路由处理]
     * 第一种 index.php/test/test
     * 第二种 index.php?c=test&f=test
     * @return [type] [description]
     */
    public static function pathInfo()
    {


        if(substr(php_sapi_name(), 0, 3) == 'cli'){

                $arr = getopt('c:f:');

                self::checkClass($arr['c'],$arr['f']);
        }else{

            if(!empty($_SERVER['PATH_INFO'])) {

                $path = $_SERVER['PATH_INFO'];

            } else {

                $path = !empty($_SERVER['REQUEST_URI']) ? str_replace(DIR_FILE,'',$_SERVER['REQUEST_URI']) : '';
                $path = preg_replace("/\?.*/isu", "", $path);

            }
            if($path) {

                $arr = explode('/',ltrim($path,'/'));
                if(!empty($_GET['c']) && !empty($_GET['f'])) {

                    self::checkClass($_GET['c'],$_GET['f']);

                } else if(!empty($arr[0]) && !empty($arr[1])) {

                    self::checkClass($arr[0],$arr[1]);

                } else if(!empty($arr[0]) && empty($arr[1]) && isset(L('urlMap')[$arr[0]])) {

                    $arr = L('urlMap')[$arr[0]];

                    self::checkClass($arr[0],$arr[1]);

                }
            }
        //SendHttpStatusCode(500);
        }
    }
    /**
     * [checkClass 检查类和方法是否合法]
     * @param  [string] $class [类名]
     * @param  string $func  [方法名]
     * @return [type]        []
     */
    public static function checkClass($class = '', $func = '')
    {
        //类文件首字母转大写
        $cla = ucfirst($class);
        $cla = strpos($cla,'Controller') ? $cla : $cla.'Controller';
        if(class_exists($cla)) {
            $cla = new $cla();
            if(method_exists($cla,$func)) {
                call_user_func_array([$cla,$func],self::$arr);
            }
            else {
                die('方法不存在');
            }
        } else {
            die('类不存在');
        }
    }
    public static function loadCoreClass($value, $key = null) {
        if(!is_array($value))
            return $value;
        foreach($value as $k => $v) {
            $v = self::loadCoreClass($v, is_null($key) ? $k : $key.DS.$k);
            if(!empty($v)) {
                 $dependentFile = empty(!is_string($key) ? : $key.DS.$v.'.php') ?  : include $key.DS.$v.'.php';
                 if(!empty($dependentFile) && is_array($dependentFile))
                     C($dependentFile);
            }
        }
    }
}
