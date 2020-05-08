<?php
namespace Piz;
class RedisPool
{
    private static $instance;
    private function __construct ()
    {
        try{	
			self::$instance = new \Swoole\Database\RedisPool((new \Swoole\Database\RedisConfig)
	        ->withHost(config('redis.host'))
	        ->withPort(config('redis.port'))
	        ->withAuth(config('redis.passwd'))
	        ->withDbIndex(config('redis.db'))
	        ->withTimeout(config('redis.timeout'))
	        );
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