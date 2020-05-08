<?php
namespace Piz;
class Redis
{
    private static $instance;
    private function __construct ()
    {
        try{
            self::$instance = new \Redis();
            self::$instance->pconnect (config('redis.host'),config ('redis.port'));
            if(config ('redis.passwd')!=''){
                self::$instance->auth(config ('redis.passwd'));
            }
            self::$instance->select(config ('redis.db'));
//          \Piz\Log::get_instance()->write ("INFO","REDIS","已连接",config('redis.host').":".config ('redis.port')); //如果前面未设置$server无法写入
        }catch (\RedisException $e){
            self::$instance = NULL ;
            \Piz\Log::get_instance()->write ("INFO","REDIS",$e->getMessage ());
			throw new \Exception($e->__toString(), 400);  //抛出一个Exception,可以被catch
        }
    }

    public static function get_instance(){
        if(is_null (self::$instance)){
            new self();
        }
        return self::$instance;
    }

    public function __call($method ,$args=NULL){
        $this->handle->$method(...$args);
    }
}