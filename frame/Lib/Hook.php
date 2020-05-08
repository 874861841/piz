<?php
namespace Piz;
//钩子的说白了就是调用目的函数前调用另外一个一个函数，这个函数里面注册事件控制调用哪个目的函数或类的方法
class Hook
{
    private static $instance;
    private static $config ;

    private function __construct ()
    {
    }

    public static function get_instance(){
        if(is_null (self::$instance)){
            self::$instance = new self();
            self::$config =  Config::get_instance ()->get("hook");    //钩子的注册事件，注册了方法
        }
        return self::$instance;
    }

    public function listen($hook , ...$args){
        $hooks = isset(self::$config[$hook]) ? self::$config[$hook] : [] ;
        while($hooks){
            list($class,$func) = array_shift($hooks);
            try{
                $class::get_instance()->$func(...$args);     //触发事件
            }catch (\Exception $e){
                Log::get_instance ()->write ('ERROR',$e->getMessage ());
            }
        }
    }
}