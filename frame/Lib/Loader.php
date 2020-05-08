<?php
/**
 * 加载器
 */
namespace Piz;

class Loader
{
	public function __construct ($controller){
		$this -> controller = $controller;
	}
	  
    protected static $map = [] ;  // 类名映射,用于保存类对应的文件名，具体如下
//	array(7) {
//	["Piz\Config"]=>
//	string(48) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/Config.php"
//	["Piz\Server"]=>
//	string(48) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/Server.php"
//	["Piz\Log"]=>
//	string(45) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/Log.php"
//	["Piz\App"]=>
//	string(45) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/App.php"
//	["Piz\Request"]=>
//	string(49) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/Request.php"
//	["Piz\Router"]=>
//	string(48) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/Router.php"
//	["app\modules\index\index"]=>
//	string(55) "/mnt/hgfs/centos7.6/php/piz/app/modules/index/index.php"
//	}

    protected static $namespaces = [] ;  //命名空间前缀映射，用保存命名空间 前缀对应的 目录如 下
//  array(2) {
//	["Piz"]=>
//	string(38) "/mnt/hgfs/centos7.6/php/piz/frame/Lib/"
//	["app"]=>
//	string(32) "/mnt/hgfs/centos7.6/php/piz/app/"
//	}   
    

    public static function register(){
        spl_autoload_register ('\\Piz\\Loader::autoload',true , true );
        self::addNamespace ('Piz',__DIR__.'/');  //命名空间 Piz 对应的路径就是本目录（其实这么做也不一定是个好办法）
    }
    public static function autoload($class){
        if($file = self::find($class)){
            include $file;
            return true;
        }
    }

    //查找文件，并映射到$map
    private static function find($class){
        if(!empty(self::$map[$class])){   //如果已存在就直接返回
            return self::$map[$class];
        }
        
        $classes = array_filter(explode ('\\',$class )); //array_filter 用于过滤数组中的值为false 或者 null 的元素
        
        $namespace = array_shift ($classes);  //删除数组中的第一个元素，并返回被删除的元素
        //join函数 将数组里面的元素连接起来，第一个参数是连接的符号
        $logicalPath  = join (DIRECTORY_SEPARATOR ,$classes) .'.php'; // DIRECTORY_SEPARATOR 根据系统判断系统分隔符，windows下的是\和/，而LINUX下的是/
        if(isset(self::$namespaces[$namespace])){	// 如果命名空间已注册(这个命名空间不是真正的命名空间，只是完整命名空间的最前面的路径)，那就往下找。
            $dir = self::$namespaces[$namespace] ;  //获取到对应的目录
            if(is_file ($path = $dir.$logicalPath)){
                self::$map[$class] = $path;
                return $path;
            }
            echo "{$dir}{$logicalPath} 找啊找，找不到，你说气人不气人",PHP_EOL;
        }
        return  false;
    }

    // 注册 类
    public static function addMap($class , $map = ''){
        self::$map[$class] = $map ;
    }
	
    // 注册命名空间
    public static function addNamespace($namespace,$path=''){
        self::$namespaces[$namespace] = rtrim($path,'/').DIRECTORY_SEPARATOR;
    }
	
	public function model($modelPath,$modelName){
		$class = "\\app\\models\\".str_replace('/','\\',$modelPath);
		$this->controller->$modelName = new $class();
    }
	
	public function redis($redisName = 'redis'){
		$this->controller->$redisName = \Piz\Redis::get_instance();;
    }
	
}