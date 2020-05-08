<?php
namespace app\controllers\api\lib;
class queue{
	/**
     * 实例
     * @var object
     */
    private static $instance ;
    /**
     * 配置参数
     * @var array
     */
    private static $config = [];

	private $server;
	
    private function __construct ()
    {
    }

    public static function get_instance(){
        if(is_null(self::$instance)){
            self::$config = \Piz\Config::get_instance ()->get('app.log');
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	final public function set_server($server){
        self::$instance->server = $server;
        return self::$instance;
    }
	
	//记录点击人数
	public function insertRushUser($rushId,$userId,$buy_quantity){    //可变数量的参数
		$this->task = \Piz\Task::get_instance()->set_server($this->server);
		$this->task->delivery (\app\task\insertRushUser::class,'insert',[$rushId,$userId,$buy_quantity]); //投递异步任务
    }
	
	//一个一个匹配队列
	public function rushOrderMatching($rushId,$userId,$buy_quantity,$buy_quantity_true,$host){    //可变数量的参数
		$this->task = \Piz\Task::get_instance()->set_server($this->server);
		$this->task->delivery (\app\task\rushOrderMatching::class,'Matching',[$rushId,$userId,$buy_quantity,$buy_quantity_true,$host]); //投递异步任务
    }
}
?>