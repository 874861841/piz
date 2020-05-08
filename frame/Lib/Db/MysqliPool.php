<?php
namespace Piz\Db;
class MysqliPool
{
    public static $conns = [];
    private function __construct ($config)
    {
        try{	
			self::$conns[$config['hostname']] = new \Swoole\Database\MysqliPool((new \Swoole\Database\MysqliConfig)
	        ->withHost($config['hostname'])
	        ->withPort($config['port'])
	        // ->withUnixSocket('/tmp/mysql.sock')
	        ->withDbName($config['database'])
	        ->withCharset('utf8mb4')
	        ->withUsername($config['username'])
	        ->withPassword($config['password'])
	        );
			
        }catch (\Exception $e){
            \Piz\Log::get_instance()->write ("INFO","Mysqli",$e->getMessage ());
            throw new \Exception($e->__toString(), 400); //抛出一个Exception,可以被catch
        }
    }

    public static function get_instance($config = [])
    {
	    if(!isset(self::$conns[$config['hostname']])){
	        new self($config);
	    }
		
	    return self::$conns[$config['hostname']];
        
    }

    public function __call($method ,$args=NULL){
        $this->handle->$method(...$args);
    }
}