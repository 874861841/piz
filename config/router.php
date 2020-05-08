<?php
/**
 * 路由配置
 */

return [
    'm'             => 'index',     //默认模块
    'c'             => 'index',     //默认控制器
    'a'             => 'init',     //默认操作
    'ext'           => '.html',          //url后缀    例如 .html
    'rules'         =>  [           //自定义路由
        'user'  => 'uesr/index/init',
        'login' => 'index/login/init',
    ]
];