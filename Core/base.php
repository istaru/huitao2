<?php
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
//项目目录
defined('DIR_APP') or define('DIR_APP',DIR.DS.'Applications'.DS);
//缓存以及日志目录
defined('DIR_RUNTIME') or define('DIR_RUNTIME', DIR.DS.'runtime'.DS);
defined('DIR_RUNTIME_LOG') or define('DIR_RUNTIME_LOG', DIR_RUNTIME.'logs'.DS);
//控制器目录
defined('DIR_CONTROLLER') or define('DIR_CONTROLLER',DIR_APP.'Controllers'.DS);
//模型目录
defined('DIR_MODEL')      or define('DIR_MODEL',DIR_APP.'Models'.DS);
//视图目录
defined('DIR_VIEW')       or define('DIR_VIEW',DIR_APP.'Views'.DS);
//框架主目录
defined('DIR_CORE')       or define('DIR_CORE',dirname(__file__).DS);
//模板目录
defined('DIR_TPL')        or define('DIR_TPL', DIR_CORE.'tpl'.DS);
//第三方插件目录
defined('DIR_LIB')        or define('DIR_LIB',DIR.DS.'lib'.DS);
//公共函数库目录
defined('DIR_COMMON')     or define('DIR_COMMON',DIR_CORE.'common'.DS);
//框架类库目录
defined('DIR_CORE_FILE')  or define('DIR_CORE_FILE',DIR_CORE.'core'.DS);
defined('EXT')            or define('EXT','.php');
/**
 * ----------------------------华丽的分割线------------------------------------
 */
//加载公共函数库
if(is_file(DIR_COMMON.'function'.EXT))
    require DIR_COMMON.'function'.EXT;
//注册自动加载类
spl_autoload_register(function($className) {
    $route = [DIR_CONTROLLER, DIR_MODEL,DIR_CORE_FILE];
    foreach($route as $v) {
        if(is_file($v.$className.EXT)) {
            include $v.$className.EXT;
            return;
        }
    }
});
new Route;