<?php
/**
 * APP配置
 */
return [
    'name'      => 'server',                            //项目名称
    'namespace' => 'app',                               //项目命名空间
    'path'      => realpath (__DIR__.'/../app/'),  //项目所在路径
    'gzip'      => 0,                                    //gzip 等级， 请查看  https://wiki.swoole.com/wiki/page/410.html

    //server设置
    'ip'        => '0.0.0.0',   //监听IP
    'port'      => 9501,        //监听端口
    'server'    => 'http' ,     //服务，可选 websocket 默认http

    'set'       => [            //配置参数  请查看  https://wiki.swoole.com/wiki/page/274.html
        'daemonize'             => 0 ,
        'enable_static_handler' => TRUE ,
        'document_root'         => realpath (__DIR__.'/../static/') ,
        'worker_num'            => 20,
        'max_request'		 => 1000,
        'task_worker_num'       => 20,
        'task_ipc_mode'         =>2,
//      'task_enable_coroutine' => true,   //开启后可以在task中使用协程
        'reload_async' => true,
//      'enable_coroutine' => false, //关闭内置协程,如果不需要用协程关闭这个会提高一些性能 https://wiki.swoole.com/#/server/setting
    ],

    //监控配置
    'monitor'   =>  [
        'timer'     =>  3000 ,  //定时器间隔时间，单位毫秒
        'restart'   => 1 ,       //重启
    ] ,

    //日志
    'log' => [
        //输出到屏幕，当 set.daemonize = false 时，该配置生效，
        'echo'  => 0 ,
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别，共8个级别
        'level' => ['EMERGENCY','ALERT','CRITICAL','ERROR','WARNING','NOTICE','INFO','DEBUG','SQL'] ,
    ] ,

];
