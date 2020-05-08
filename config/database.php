<?php
/**
 * 数据库配置
 */
// require_once('/var/www/database.php');
return [
    'default' => [
        'hostname' => 'mfamysql1.rwlb.rds.aliyuncs.com',		//服务器地址
        'port' => 3306,				    //数据库连接端口
        'database' => 'mfa_db',			// 数据库名
        'username' => 'jancojie',			// 数据库用户名
        'password' => '*$$)*!*(jancO',			// 数据库密码
        'charset' => 'utf8',			// 数据库编码默认采用utf8
        'debug' => true,                // 调试模式
        'pconnect' => true,			    // 长连接
    ],
    'test' => [
        'hostname' => 'mfamysql1.rwlb.rds.aliyuncs.com',		//服务器地址
        'port' => 3306,				    //数据库连接端口
        'database' => 'mfa_db_test',			// 数据库名
        'username' => 'jancojie',			// 数据库用户名
        'password' => '*$$)*!*(jancO',			// 数据库密码
        'charset' => 'utf8',			// 数据库编码默认采用utf8
        'debug' => true,                // 调试模式
        'pconnect' => true,			    // 长连接
    ],
    'kingshan' => [
        'hostname' => '127.0.0.1',        //服务器地址
        'port' => 3306,                 //数据库连接端口
        'database' => 'ea888',            // 数据库名
        'username' => 'root',           // 数据库用户名
        'password' => '@#yueG1G128...*',          // 数据库密码
        'charset' => 'utf8',            // 数据库编码默认采用utf8
        'debug' => true,                // 调试模式
        'pconnect' => true,             // 长连接
    ],
    // 'online' => $online, //如果未定义的话只会报一次错误，因为单例模式引入本文件，而且是notice 错误，可以继续运行，所以只报一次错误，如果是编译类型的错误，则会终止
];