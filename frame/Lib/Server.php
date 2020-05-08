<?php
namespace Piz;
class Server
{
    private static $instance;
    public $server ;
	public $serverType;
	public $ip;
	public $port;
    private $config ;
    private $workerId;
    public $name ;
    private function __construct (){}
    private function __clone() {}

    public static function get_instance(){
        if(is_null (self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set_config($config){
        $this->config = $config;
        $this->name = $config['name'];
		$this->port = $config['port'];
		$this->ip = $config['ip'];
    }
	
	public function set_server_type($serverType){
        $this->serverType = $serverType;
    }
	
	public function set_ip(string $ip){
        $this->ip = $ip;
    }
	
	public function set_port(int $port){
        $this->port = $port;
    }

    public function run(){
        $swoole_server = isset($this->serverType) && $this->serverType == 'websocket'?'swoole_websocket_server':'swoole_http_server'; //配置文件的设置
       
        $port = isset($this->port) && intval($this->port) ? $this->port : 9501;
		
		$ip = isset($this->ip) && ip2long($this->ip) ? $this->ip :'0.0.0.0';

        $this->server = new $swoole_server($ip,$port);

        $this->server->set($this->config['set']);


        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('workerstart',[$this,'onWorkerStart']);
        $this->server->on('workerstop', [$this, 'onWorkerStop']);
        $this->server->on('workererror',[$this,'onWorkerError']);
        $this->server->on('managerStart', [$this, 'onManagerStart']);
        $this->server->on('managerStop', [$this, 'onManagerStop']);

        if($swoole_server === 'swoole_websocket_server'){
            $this->server->on('open' ,[$this,'onOpen']);
            $this->server->on('message',[$this,'onMessage']);
            $this->server->on('close',[$this,'onClose']);
        }
        $this->server->on('request' ,[$this,'onRequest']);
		
        if( isset($this->config['set']['task_worker_num']) && $this->config['set']['task_worker_num']>0){
            $this->server->on('task',[$this,'onTask']);
            $this->server->on('finish',[$this,'onFinish']);
        }

        $this->server->start();
		
    }

    public function onStart($server){
    	Log::get_instance()->set_server($server)->write('DEBUG',$this->name,"主进程启动");  //这里单例的Log不能被后面的子进程代码使用，后面代码使用会重新实例化
        $this->set_process_title($this->name .'-'. $this->serverType . '-' . $this->port. '-master');
//      Hook::get_instance ()->listen('start',$server);
    }

    /**
     * Worker进程/Task进程启动时
     */
    public function onWorkerStart($server,$workder_id){
        // echo '进程'.$workder_id.'启动';
//  	var_dump(get_included_files()); //查看 reload 前就加载的文件
        $this->workerId = $workder_id;
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
		
        // 重新加载配置
//      $this->reload_config();
        if (!$server->taskworker) {//worker进程
            $this->set_process_title($this->name .'-'. $this->serverType . '-' . $this->port. "-worker");
        } else {
            $this->set_process_title($this->name .'-'. $this->serverType . '-' . $this->port. "-tasker");
        }
        //官方的要求建立redis连接 https://wiki.swoole.com/wiki/page/325.html
        // $server->redis = \Piz\Redis::get_instance();
//      //每3秒执行一次,每个进程都执行
//      swoole_timer_tick(3000,function ($time_id){
//          Log::get_instance ()->save();
//      });

//		\Swoole\Timer::tick(3000, function (int $timer_id, $param1, $param2) {
//  		echo "timer_id #$timer_id, after 3000ms.\n"; 
//  		echo "param1 is $param1, param2 is $param2.\n";  //其中param1 是A  param2 是B
//		}, "A", "B");
		
    }
    /**
     * 当worker/task_worker进程发生异常
     */
    public function onWorkerError($server, $worker_id, $worker_pid, $exit_code){
        Log::get_instance()->set_server ($server)->write('ERROR',$this->name,"进程异常","WorkerID:{$worker_id}","WorkerPID:{$worker_pid}","ExitCode:{$exit_code}");
    }
    /**
     * worker进程终止时
     * @param  $server
     * @param  $worker_id
     */
    public function onWorkerStop($server, $worker_id){
    	echo '进程终止,请查询是不是reload导致';
//      Log::get_instance()->set_server ($server)->write('ERROR',$this->name,"进程终止","WorkerID:{$worker_id}");    //先关闭，因为在task 进程不能向 task 进行投递任务，
    }

    /**
     * 当管理进程启动时
     * @param $server
     */
    public function onManagerStart($server){
        $this->set_process_title($this->name .'-'. $this->serverType . '-' . $this->port. '-manager');
        Log::get_instance()->set_server ($server)->write('DEBUG',$this->name,"管理进程启动");
    }
    /**
     * 当管理进程结束时
     * @param $server
     */
    public function onManagerStop($server){
        Log::get_instance()->set_server ($server)->write('DEBUG',$this->name,"管理进程结束");
    }

	//websocket 建立连接
    public function onOpen( $server,$request){
        Log::get_instance()->set_server ($server)->write('DEBUG',"FD:{$request->fd}","握手成功,建立了连接");
//      Hook::get_instance ()->listen('open',$server,$request->fd);
    }

    //websoket关闭连接回调
    public function onClose($server,$fd){
        Log::get_instance()->set_server ($server)->write('DEBUG',"FD:{$fd}","关闭连接");
//      Hook::get_instance ()->listen('close',$server,$fd);
    }
	
	//http 请求
    public function onRequest($request,$response){
		//注册异常处理函数，预防try catch 获取不到
		Error::get_instance($request,$response);		
		
		//解决谷歌浏览器等重复调用的问题
        if($this->config['set']['enable_static_handler'] && $request->server['request_uri'] == '/favicon.ico'){
            return ;
        }
		//设置相应头部，处理返回前端乱码
		$response->header("Server", "SwooleServer");
//      $response->header("Content-Type", "text/html; charset=utf-8");
		$response->header('Content-Type',"application/json");
		//根据url处理请求做出响应
        ServerResponse::get_instance()->http($this->server,$request,$response);
    }

	//websocket 发送信息时
    public function onMessage($server,$frame){
        Log::get_instance()->set_server ($server)->write('INFO',"FD:{$frame->fd}","Opcode:{$frame->opcode}","Finish:{$frame->finish}","Data:{$frame->data}");
        ServerResponse::get_instance()->websocket($server,$frame);
    }

	//异步任务回调，如果配置文件开通task进程设置可使用协程  则回调的参数会变少而发生报错 参考：https://wiki.swoole.com/wiki/page/54.html
    public function onTask($server,$task_id,$workder_id,$data){
//      return Task::get_instance ($server)->dispatch ($task_id,$workder_id,$data);
		return Task::get_instance()->set_server ($server)->dispatch ($task_id,$workder_id,$data);
//		$server->finish($data);
    }

	//完成任务投递回调
    public function onFinish($server,$task_id,$data){
//      Task::get_instance ($server)->finish($task_id,$data);
		Task::get_instance()->set_server ($server)->finish($task_id,$data);
    }

    /**
     * 获取 来自哪个监听端口
     * @param $fd
     * @return mixed
     */
    public function get_server_port($fd){
        return $this->server->connection_info($fd)['server_port'];
    }

    /**
     * 重新加载配置
     */
    public function reload_config(){
        $this->config = config('app');
        $this->name = $this->config['name'];
    }
    /**
     * Set process name.
     * @param string $title
     * @return void
     */
    public function set_process_title($title)
    {
        if(PHP_OS === 'Darwin')  return ;
        // >=php 5.5
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        }else {
            @swoole_set_process_name($title);
        }
    }


}