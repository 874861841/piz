<?php
/**
 * http Controller
 */
namespace Piz;
class Controller
{
	/**
     * @var 根路径，ip地址和端口号
     */
    protected $server_root_route;
    /**
     * @var \Piz\Router
     */
    protected $router;
    /**
     * @var swoole_http_server->request
     */
    protected $request;
    /**
     * @var swoole_http_server->response
     */
    protected $response;
    /**
     * @var swoole_server
     */
    protected $server;
    /**
     * @var 加载器类
     */    
    protected $load;
    /**
     * @var 日志类
     */
     protected $log;
    /**
     * @var 
     */    
    protected $redis;
    /**
     * @var 
     */
     
    protected $task;
    /**
     * 渲染输出JSON
     * @param array $array
     * @param null  $callback
     */
     
    public function __construct ($server,$request,$response,$router)
    {
    	$this->server = $server;
    	$this->request = $request;
		$this->response = $response;
		$this->router = $router;
		//实例化load 方法
		$this->load = new LibLoader($this);
		//实例化redis
//		$this->redis = \Piz\Redis::get_instance ();
		//单例模式初始化一些必要的操作，如果是有变量类型的在 get_instance 函数里面判断
		$initialize = \Piz\Initialize::get_instance($server,$request,$response,$router);
		$this->server_root_route = $initialize->server_root_route;
		//写入日志 //没有这一步的set_server话 ，在redis 类里面不能写日志会报 Error: Call to a member function task() on null in /var/www/piz/frame/Lib/Task.php:41
		// $this->log = \Piz\Log::get_instance()->set_server ($this->server); 
		// $this->log->write('INFO','post :'.json_encode($this->request->post),'get :'.json_encode($this->request->get));
		
		//实例化redis，暂时以异步任务方式写入日志
		// $this->redis = \Piz\Redis::get_instance();
    }
	
	/**
     * @descrition 文件上传
	 * @return 
	 * array(2) {
		  ["baidu"]=>
		  string(90) "192.168.21.128:80/uploads/images/2019-10-22/1571725537c2edf23fb5232b89cbe71673306e6485.png"
		  ["baid"]=>
		  string(90) "192.168.21.128:80/uploads/images/2019-10-22/1571725537a937cf21a208414659bd17911e8ee5bf.png"
		}
     */
	public function uploadImage() {
       $filenameArr = \Piz\Upload::get_instance()->set_request ($this->request)->do_upload();
	   foreach ($filenameArr as $key => $value) {
	   		$filenameArr[$key] = $this->server_root_route.$value;
	   }
	   return $filenameArr;
    }
	
	/*
	 * @descrition 析构函数
	 **/
	public function __destruct() {
//      echo 'byby';
    }
	 
	/*如果父类中的方法被声明为 final，则子类无法覆盖该方法。如果一个类被声明为 final，则不能被继承。*/
    final public function json($array=array(),$callback=null){
        $this->gzip ();
        $this->response->header('Content-type','application/json');
        $json = json_encode($array);
        $json = is_null($callback) ? $json : "{$callback}({$json})" ;
        $this->response->end($json);
    }

    /**
     * 渲染模板
     * @param null $file 为空时，
     * @param bool $return true 返回值，false 仅include
     * @return string
     */
    final public function display($param = array() ,$return = false){
        if(!is_array ($param)){
            Log::get_instance()->set_server ($this->server)->write('WARNING',"参数类型必须为key=>val式的数组");
        }
        extract($param);
        $this->gzip ();
        $path = Config::get_instance()->get('app.path').'/tpl/'.$this->route['m'].'/'.$this->route['c'] .'/'.$this->route['a'].'.php';
        if(!file_exists ($path)){
            $this->response->status(404);
            $this->response->end("模板不存在：".$path);
            Log::get_instance()->set_server ($this->server)->write('WARNING',"模板不存在",$path);
            return ;
        }
        if(!empty(ob_get_contents())) ob_end_clean ();
        ob_start();
        include $path;
        $content = ob_get_contents();
        ob_end_clean();
        $this->response->end($content);
    }
	
	/*
	 * 判断必填参数是否存在并且不为空
	 * 返回json数据
	 */
	public function judgeParam(array $arrNeed,string $inputType = 'POST')
	{
		//判断请求方式是否正确	
		if($this->request->server['request_method'] !== $inputType) return array('state'=>FALSE,'message'=>'请求方式不正确');
		
        $arrAll = 'POST'===$inputType?array_keys($this->request->post):array_keys($this->request->get);
		$flag = array_diff($arrNeed, array_intersect($arrAll, $arrNeed));
		if(empty($flag)){
            //判断参数是否为空
            foreach ($arrNeed as $value) {
                if($this->request->post[$value]===''||is_null($this->request->post[$value])){
                    $flag[] = $value;
                }
            }
            if(!empty($flag)){
                $paramEmpty = implode(";", $flag);
				$message = '参数错误,参数'.$paramEmpty.'为空';
				return array('state'=>TRUE,'message'=>$message);
//              $this->returnJson('400',null,'参数错误,参数'.$paramEmpty.'为空');
            }
			return array('state'=>TRUE,'message'=>NULL);
		}else {
			$paramLess = implode(";", $flag);
			$message = '参数错误，请检查必填参数'.$paramLess;
//          $this->returnJson('400',null,'参数错误，请检查必填参数'.$paramLess);
			return array('state'=>FALSE,'message'=>$message);
		}
		return array('state'=>TRUE,'message'=>NULL);
    }
	
	/**
	 * @return 返回json数据
	 */
    protected function echoJson($code,$data = array(),$message = '',$status = 1){
        $returnArray = array('code'=>$code,'data'=>$data,'message'=>$message,'status'=>$status);	       
        echo json_encode($returnArray);
    }

    /**
     * 启用Http GZIP压缩
     * $level 压缩等级，范围是1-9
     */
    final public function gzip($level = NULL  ){
        if($level === NULL ){
            $level = Config::get_instance ()->get('app.gzip',0);
        }
        $level>0 && $this->response->gzip( $level);
    }

    public function __set($name,$object){
        $this->$name = $object;
    }

    public function __get($name){
        return $this->$name;
    }

}