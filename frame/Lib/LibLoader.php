<?php
/**
 * 加载器
 */
namespace Piz;

class LibLoader
{
	private $controller;
	
	public function __construct ($controller){
		$this->controller = $controller;
	}
	
	public function model($modelPath,$modelName){
		$class = "\\app\\models\\".str_replace('/','\\',$modelPath);
		$this->controller->$modelName = new $class();
		return $this->controller->$modelName;
    }
	
	public function redis($redisName = 'redis'){
		$this->controller->$redisName = \Piz\Redis::get_instance();
		return $this->controller->$redisName;  //返回会浪费内存
    }
	
	public function redisPool($redisPoolName = 'redisPool'){
		$this->controller->$redisPoolName = \Piz\RedisPool::get_instance(); //单例获取池效率更高
    }
	
}