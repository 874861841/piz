<?php
/**
 * Router
 */

namespace Piz;

class Router
{
    /**实例
     * @var object
     */
    private static $instance;
    //默认配置
    private static $config = [];
    private function __construct() {}
    /**
     * 获取实例
     * @return object
     */
    public static function get_instance() {
        if( is_null(self::$instance) ) {
            self::$instance = new self();
            self::$config = Config::get_instance ()->get('router');
        }
        return self::$instance;
    }
    /**
     * WebSocket 路由解析
     */
    public function websocket($data) {

        $data = json_decode ($data , true );
        if(empty($data)){
            echo 'WEBSOCKET-json解包错误',PHP_EOL;
            return ['m'=>NULL ,'c'=>NULL,'a'=>NULL ,'p' =>  NULL] ;
        }

        $path = empty($data['cmd']) ? '' : trim($data['cmd'], '/');

        if(empty($path)){
            echo '请求地址错误',PHP_EOL;
            return ['m'=>NULL ,'c'=>NULL,'a'=>NULL ,'p' =>  NULL] ;
        }

        if (!empty(self::$config['rules']) && isset(self::$config['rules'][$path])) {
            $path =  self::$config['rules'][$path];
        }

        $param = explode( "/" , $path);

        $module     =   array_shift ($param);
        $controller =   array_shift ($param);
        $action     =   array_shift ($param);
        unset($data['cmd']);
        return ['m'=>$module ,'c'=>$controller,'a'=>$action ,'p' =>  $data] ;
    }

    /**
     * 这里就要一个参数 swoole_http_server->request->server[request_uri]
     */
    public function http($request_uri){
        $param 		= [];
        $module 	= self::$config['m'];
        $controller = self::$config['c'];
        $action 	= self::$config['a'];

        if(empty($request_uri)) {
            return ['m'=>$module ,'c'=>$controller,'a'=>$action,'p'=>$param];
        }

        $path = trim($request_uri, '/');

        if(!empty( self::$config['ext']) &&substr($path,-strlen(self::$config['ext'])) == self::$config['ext'] ){
            $path = substr($path , 0 , strlen($path)-strlen(self::$config['ext']));
        }

        if (!empty(self::$config['rules']) ) {
            foreach (self::$config['rules'] as $key => $value) {
                if(substr($path,0,strlen($key)) == $key) {
                    $path = str_replace($key, $value, $path);
                    break;
                }
            }
        }

        $param = explode( "/" , $path);
        !empty($param[0]) && $module = $param[0];
        isset($param[1]) && $controller = $param[1];
        isset($param[2]) && $action = $param[2] ;

        if(count($param)>=3){
            $paramArr = array_slice($param, 3);
        }else{
            $paramArr = array_slice($param, 2);
        }
		
		if(strpos('v1v2v3v4v5v6',$param[1]) !== false){
			return ['m'=>$module ,'c'=>$param[2],'a'=>$param[3],'p'=>$param];
		}

        return ['m'=>$module ,'c'=>$controller,'a'=>$action,'p'=>$param];
    }
}