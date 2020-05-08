<?php
/**
 * 管理FD
 */
namespace app\hook ;
class FD
{
    private static $instance;

    private function __construct ()
    {
    }

    public static function get_instance(){
        if(is_null (self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start($server){
        \Piz\Redis::get_instance ()->del("FD");
        \Piz\Log::get_instance()->write ("INFO","Hook","重置FD表");
    }

    public function open($server,$fd){
        \Piz\Redis::get_instance ()->sAdd("FD",$fd);
        \Piz\Log::get_instance()->write ("INFO","Hook","写入REDIS集合","FD:{$fd}");
    }


    public function close($server,$fd){
        \Piz\Redis::get_instance ()->sRem("FD",$fd);
        \Piz\Log::get_instance()->write ("INFO","Hook","移出REDIS集合","FD:{$fd}");
    }

    public function __call($method ,$args=NULL){
        $this->$method(...$args);
    }
}