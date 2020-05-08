<?php
namespace Piz;
/**
 * 异常处理类
 */
class Error{
	
	private static $instance;
	private $request;
	private $response;
	
	public function __construct($request,$response) {
		$this->request = $request;
		$this->response = $response;
		error_reporting(E_ALL); 
		ini_set("display_errors", 1);
		//注册异常获取函数，错误获取函数，和程序终止的回调函数，部分错误try catch 不能捕获，如notice，waming等，如果已经被捕获一次，则不会再次被捕获或者在命令行输出
		@set_exception_handler(array($this, 'exception_handler'));
		@set_error_handler(array($this, 'error_handler')); //优先级比try cath 高
		@register_shutdown_function(array($this, 'shutdown_function')); //当我们的脚本执行完成或意外死掉导致PHP执行即将关闭时,我们的这个函数将会被调用
		
		//注册一个会在php中止时执行的函数(终止后的回调函数，神器)
//		register_shutdown_function(function ()use($response){
//		    //获取最后发生的错误
//		    $error = error_get_last();
//		    if (!empty($error)) {
////		        echo $error['message'], '<br>';
////		        echo $error['file'], ' ', $error['line'];
//				$response->end($error['message']);
//		    }
//		});
	}
	
	public static function get_instance($request,$response){
        if(is_null (self::$instance)){
            self::$instance = new self($request,$response);
        }
        return self::$instance;
    }
	
	final public function error_handler($errno, $errstr ,$errfile, $errline) {
		if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    	}
    	// echo "Error catch\n";
	    switch ($errno) {
		    case E_USER_ERROR:   //致命的用户生成的错误。这类似于程序员使用 PHP 函数 trigger_error() 设置的 E_ERROR。
		        echo "Error catch\nPHP User_Error: $errstr in $errfile on line $errline\n";
		        break;
				
			case E_RECOVERABLE_ERROR:   //可捕获的致命错误。类似 E_ERROR，但可被用户定义的处理程序捕获。(参见 set_error_handler())
		        echo "Error catch\nPHP User_Recoverable_Error: $errstr in $errfile on line $errline\n";
		        break;
				
			case E_NOTICE:   //Run-time 通知。脚本发现可能有错误发生，但也可能在脚本正常运行时发生。
		        echo "Error catch\nPHP Notice: $errstr in $errfile on line $errline\n";
		        break;
				
			case E_WARNING:    //非致命的 run-time 错误。不暂停脚本执行。
				if(strpos($errstr,'MySQL server has gone away') !== false || strpos($errstr,"Couldn't fetch mysqli") !== false){
					// echo "Error catch\nPHP Warning: $errstr in $errfile on line $errline\n";  //mysqli连接错误不输出错误
					//mysql 已经断开，需要重置连接
	 				// \Piz\Db\Mysqli::$conns = [];
					break;
				}
				echo "Error catch\nPHP Warning: $errstr in $errfile on line $errline\n";
		        break;
		
		    case E_USER_WARNING:   //非致命的用户生成的警告。这类似于程序员使用 PHP 函数 trigger_error() 设置的 E_WARNING。
		        echo "Error catch\nPHP User_Warning: $errstr in $errfile on line $errline\n";
		        break;
		
		    case E_USER_NOTICE:   //用户生成的通知。这类似于程序员使用 PHP 函数 trigger_error() 设置的 E_NOTICE。
		        echo "Error catch\nPHP User_Notice: $errstr in $errfile on line $errline\n";
		        break;
				
			case E_DEPRECATED:   //
		        echo "Error catch\nPHP Deprecated: $errstr in $errfile on line $errline\n";
		        break;
				
			case E_USER_DEPRECATED:   //
		        echo "Error catch\nPHP User_Deprecated: $errstr in $errfile on line $errline\n";
		        break;
		        				
		    default:   //其他错误
				echo "Error catch\nPHP errno: $errno $errstr in $errfile on line $errline\n";
//		        echo "Unknown error type: [$errno] $errstr<br />\n";
		        break;
		}
   	}
	
	final public function exception_handler(Throwable $e) {
		if ($e instanceof Error)
    	{
        	echo "catch Error: " . $e->getCode() . '   ' . $e->getMessage() . '<br>';
    	}else
	    {
	        echo "catch Exception: " . $e->getCode() . '   ' . $e->getMessage() . '<br>';
	    }
   	}
	
	final public function shutdown_function() {
		//获取最后发生的错误
		    $error = error_get_last();
		    if (!empty($error)) {
		    	echo 'Shutdown catch'.PHP_EOL;
		        echo $error['message'], '<br>';
		        echo $error['file'], ' ', $error['line'];
//				$this->response->end($error['message']);
		    }
	}
}
