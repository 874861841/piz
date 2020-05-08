<?php
/**
 * 日志
 */
namespace Piz;

class Log
{
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

    private static $logs = [] ;

	private $server;
	
    private function __construct ()
    {
    }

    public static function get_instance(){
        if(is_null(self::$instance)){
            self::$config = Config::get_instance ()->get('app.log');
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	final public function set_server($server){
        self::$instance->server = $server;
        return self::$instance;
    }

    /**
     * write 方法别名
     * @param       $type
     * @param array ...$logs
     */
    public function logs($type,...$logs){
        $this->write ($type,...$logs);
    }
    /**
     * 异步写入日志，但还是会影响并发，到时写以队列的方式写入日志
     * @param       $type
     * @param array ...$msg
     */
    public function write($type,...$logs){    //可变数量的参数
        $type = strtoupper ($type);
        $msg = "{$type} \t ".date("Y-m-d h:i:s",time())." \t ".join (" \t ",$logs);
        if( !in_array($type,self::$config['level'])) return false;
        if(self::$config['echo']){
            echo $msg,PHP_EOL;
        }
//      self::$logs[$type][]=$msg;   内存保存的方法不好，用内存，
//		$this->save();
		$this->task = Task::get_instance()->set_server($this->server);
		$this->task->delivery (\app\task\WriteLog::class,'Write',[$type,$msg]); //投递异步任务
    }
    /**
     * swoole携程写入日志信息，暂时弃用，用来配合内存保存后写入，弃用
     * @param mixed  $msg   调试信息
     * @param string $type  信息类型
     * @return bool
     */
    public function save(){
        if (empty(self::$logs)) return false;
        foreach(self::$logs as $type => $logs){
            $dir_path = LOG_PATH.date('Ymd').DIRECTORY_SEPARATOR;
            !is_dir($dir_path) && mkdir($dir_path,0777,TRUE);
            $filename  = date("H").'.'.$type.'.log';
            $content = NULL ;
            foreach($logs as $log){
                $content .= $log.PHP_EOL;
				//          swoole_async_writefile($dir_path.$filename , $content, NULL, FILE_APPEND); //抛弃异步，使用协程
				go(function ()use ($dir_path,$filename,$content) {
					file_put_contents($dir_path.$filename,$content,FILE_APPEND);
				});
            }

        }
        self::$logs = [];
        return true;
    }

	public function __destruct()
    {
    	echo 2223;
    }

}