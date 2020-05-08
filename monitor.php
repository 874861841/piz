#!/usr/bin/env php
<?php
require_once "./frame/base.php";

swoole_timer_tick(\Piz\Config::get_instance ()->get('app.monitor.timer'),function ($timer_id){
    $port = \Piz\Config::get_instance ()->get('app.port');
    $restart = \Piz\Config::get_instance ()->get('app.monitor.restart');
    $sh = "netstat -anp 2>/dev/null | grep {$port} | grep LISTEN | wc -l  ";
    $res = intval(shell_exec($sh));
    if(empty($res)){
        echo date('Y-m-d H:i:s'),"\t","服务下线",PHP_EOL;
        if($restart){
            echo shell_exec('php start.php start');
        }
    }
});