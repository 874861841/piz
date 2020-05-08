<?php
namespace Piz;
class ServerResponse
{
    //实例
    private static $instance;
    //映射表
    private static $map = [];
    //防止被一些讨厌的小伙伴不停的实例化，自己玩。
    private function __construct ()
    {
    }

    //还得让伙伴能实例化，并且能用它。。
   public static function get_instance(){
        if(is_null (self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function websocket($server,$frame)
    {
        $router = $router = Router::get_instance ()->websocket( $frame ->data );

        $app_namespace  = Config::get_instance ()->get('app.namespace');
        $module         = $router['m'] ;
        $controller     = $router['c'] ;
        $action         = $router['a'] ;
        $param          = $router['p'] ;

//      $classname = "\\{$app_namespace}\\modules\\{$module}\\{$controller}";
		$classname = "\\{$app_namespace}\\controllers\\{$module}\\{$controller}";

        if ( ! isset( self ::$map[ $classname ] ) ) {
            try{
                $class = new $classname;
                //必须继承 Piz\WsController
                if(get_parent_class ($class)!='Piz\WsController'){
                    echo "[{$classname}]  必须继承 Piz\WsController",PHP_EOL;
                    return ;
                }
                self ::$map[ $classname ] = $class;
            }catch (\Exception $e){
                echo $e->getMessage (),PHP_EOL;
                return ;
            }
        }
        try{
            self::$map[$classname]->server = $server;
            self::$map[$classname]->fd = $frame->fd;
            self::$map[$classname]->param = $param;
            self::$map[$classname]->task = Task::get_instance()->set_server ($server);
            self::$map[$classname]->$action();
        }catch(\Exception $e){
            echo $e->getMessage (),PHP_EOL;
            return ;
        }
    }

    public function http($server,$request,$response){
        if($request->server['request_uri'] == '/favicon.ico') return ;
        $req    = Request::get_instance ();
        $req->set($request);
        $router = Router::get_instance ()->http($req->server['request_uri']);

        $app_namespace  = Config::get_instance ()->get('app.namespace');
        $module         = $router['m'] ;
        $controller     = $router['c'] ;
        $action         = $router['a'] ;
        $param          = $router['p'] ;
//      $classname      = "\\{$app_namespace}\\modules\\{$module}\\{$controller}" ;
   		$classname      = "\\{$app_namespace}\\controllers\\{$module}\\{$controller}" ;
//      if(!isset(self::$map[$classname])){    //不要这个玩意，如果有了就不会再重新new 了
            //实例化控制器类
//          try{  //异常捕获不可以抛出本页 parse 错误，但是可以捕获引入的文件的parse 编译错误
//              $class = new $classname($server,$request,$response,$router);
                //必须继承 Piz\Controller
//              if(get_parent_class ($class)!='Piz\Controller'){
//                  $response->header('Content-type',"text/html;charset=utf-8;");
//                  $response->status(503);
//                  $response->end('503 Service Unavailable');
//                  echo "[{$classname}]  必须继承 Piz\Controller",PHP_EOL;
//                  return ;
//              }
//              self::$map[$classname]  = $class;
//          }catch (\Throwable $e){
////              $response->header('Content-type',"text/html;charset=utf-8;");
//             $response->header('Content-Type',"application/json");
//              $response->status(503);
//              $response->end($e->__toString());
//              return ;
//          }
//      }
		//执行具体控制器的方法，在执行此函数之前，echo var_dump 等方法打印的数据无法返回到前端
        try{
        	$class = new $classname($server,$request,$response,$router);
			self::$map[$classname]  = $class;
            //测试效果
            if(!empty(ob_get_contents ())) ob_end_clean (); //先清空缓冲区
            ob_start();//使用缓冲区捕获输出
            self::$map[$classname]->$action($param); //这里设计不用返回值，直接在控制器输出，没返回值php函数执行到最后也是会自动退出的
            $content = ob_get_contents();   //获取缓冲区的内容,比如echo var_dump 的内容
            ob_end_clean();
//			unset(self::$map[$classname]);
            $response->end($content);
        }catch(\Throwable $e){      //在此处返回 404错误的原因是因为加载器已经在查找不到文件时有说错误说明
//          $response->header('Content-type',"text/html;charset=utf-8;");
			$response->header('Content-Type',"application/json");
            $response->status(404);
            $response->end("Throwable catch\n".$e->__toString());
            return ;
        }
        return ;
    }
}