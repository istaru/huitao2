<?php
/**
 * 开启全局session
 */
session_start();
/**
 * 声明编码
 */
header("Access-Control-Allow-Origin: *");
/**
 * 定义项目目录
 */
//define('DIR', __DIR__);

define('DIR',dirname(__FILE__));

/**
 * 上线设为false 改为日志记录
 */
define('APP_DEBUG',true);
/**
 * 网站根url
 */
define('URL_SITE','http://es1.laizhuan.com');
define('RES_SITE','http://127.0.0.1/');
/**
 * 根文件名
 */
define('DIR_FILE','/'.basename(dirname(__FILE__)));
/**
 * 今日新上总条数.
 */
define('TODAY_LIMIT',3000);
require DIR.'/Core/base.php';
