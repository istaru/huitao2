<?php
/**
 * 核心配置文件 您可以选择性的利用大C函数覆盖重写
 */
return [
    /*-------------------数据库配置-------------------*/
    'database' => [
        'DB_DSN'    => 'mysql:host=localhost;dbname=laitin',
        'DB_USER'   => 'root',
        'DB_PWD'    => '123456',
        'DB_PREFIX' => 'ngw_',
        'DB_CHARSET'=> 'utf8',
    ],
    /*-------------------日志信息-------------------*/
    'log'  => [
        //是否开启日志记录每次请求
        'writeLog'  => false,
    ],
    /*-------------------view层配置-------------------*/
    'view'  => [
        //设置视图层默认存储目录
        'VIEW_TMPL_PATH' => DIR_VIEW,
        //设置视图层文件后缀
        'VIEW_SUFFIX' => '.php',
    ],
    /*-------------------redis配置-------------------*/
    'redis'   => [
        'REDIS_DNS' => '127.0.0.1',
        'REDIS_PORT' => 6379,
    ],
];