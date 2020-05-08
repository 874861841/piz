<?php
/*
 * 单例模式获取 mysqli类
 */
namespace Piz\Db;
final class Mysqli 
{
    private static $instance;
    private static $config;
    public static $conns = [];
    
    private function __construct ()
    {
        try{
            $host = self::$config['hostname'];
            $username = self::$config['username'];
            $password = self::$config['password'];
            $dbName = self::$config['database'];
            $port = self::$config['port'];
            self::$conns[$host] = $conn = new \mysqli($host,$username,$password,$dbName,$port=3306);
            if ($conn->connect_error) {
                throw new \Exception("connect to server Error: %s".$conn->connect_error, 400);  //抛出一个Exception,可以被catch
            }
            $rs = $conn->set_charset(self::$config['charset']);
            if(false === $rs){
                throw new \Exception("set_charset Error: %s", $conn->error, 400);  //抛出一个Exception,可以被catch
            }
//          echo "连接数据库 $host 成功";
        }catch (\Exception $e){
            \Piz\Log::get_instance()->write ("INFO","Mysqli",$e->getMessage ());
            throw new \Exception($e->__toString(), 400); //抛出一个Exception,可以被catch
        }
    }

    private function __clone(){}

    public static function get_instance($config = [])
    {
//      try{
//          if(!isset(self::$conns[$config['hostname']])){
//              self::$config = $config;
//              new self();
//          }   
//          return self::$conns[$config['hostname']];
//      }catch (\Throwable $e) {
//          echo 123;
//          echo $e->__toString();          
//      }
//      if () {   //->ping() 或者 ->stat
//          return self::$conns[$config['hostname']];
//      }       
//      return self::$conns[$config['hostname']];
        
//      mysqli_report(MYSQLI_REPORT_STRICT);
//      mysqli_report(MYSQLI_REPORT_OFF);
        
//      else {
//          //这里判断会判处E_WARNING 错误，且try catch 捕获不了，而set_error_handler 可以捕获，已经在Error 文件做了处理
//          if (self::$conns[$config['hostname']]->stat) {   //这么判断会导致性能问题，抛弃，mysqli 是长连接，不会自己挂掉，断网也会自己连接
//              return self::$conns[$config['hostname']];
//          }else {
//              self::$config = $config;
//              new self();                 
//          }
//      }
    if(!isset(self::$conns[$config['hostname']])){
        self::$config = $config;
        new self();
    }

    $con = self::$conns[$config['hostname']];
    //判断是否为可用的连接,如果不可用则重新实例化
    if($con->ping()){
        return $con;
    }else{
        new self();
        $con = self::$conns[$config['hostname']];
    }
    return $con;
        
    }

    public static function ping()
    {
        // foreach (self::$conns as $key => $value) {
        //  $value->ping();
        // }
    }

    public function __call($method ,$args=NULL)
    {
        $this->handle->$method(...$args);
    }
}