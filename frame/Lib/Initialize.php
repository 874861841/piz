<?php
/**
 * controller 的初始化类，目的是单例模式 获取某些属性或者加载文件，提高性能
 */
namespace Piz;

final class Initialize
{
    /**
     * 实例
     * @var object
     */
    private static $instance;
	/**
     * ip加端口 
     * @var string
     */
    public $server_root_route;
	/**
     * ip加端口 
     * @var string
     */
    public static $modules = [];

    private function __construct ($server,$request,$response,$router){    	
    	$this->server_root_route = $this->getRootIP().':'.$server->port.'/';
    }

    final public static function get_instance($server,$request,$response,$router){
        if( is_null(self::$instance)) {
            self::$instance = new self($server,$request,$response,$router);

        }
		if(!in_array($router['m'], self::$modules)) {
			require_once MYHELPER_PATH.$router['m']."_helper.php";  //静态全局引入
			self::$modules[] = $router['m'];
        }
        return self::$instance;
    }    
    
    //获取本机一个ip地址
	public function getRootIP(){
        $ipArr = swoole_get_local_ip(); //获取本地非127.0.0.0 的ip
        return array_shift($ipArr);
    }
    
}