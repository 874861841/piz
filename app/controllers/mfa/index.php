<?php
namespace app\controllers\mfa;
use app\controllers\mfa\lib\conf;
//require_once('/root/piz/vendor/autoload.php');
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use EasySwoole\Mysqli\QueryBuilder;
//use Pheanstalk\Pheanstalk;
//use Piz;
class index extends \app\core\mfa_controller
{
//	public function __destruct() {
//      echo 'byby';
//  }
    public function tellName(){
    	// create a log channel
//		$log = new Logger('name');
//		$log->pushHandler(new StreamHandler('/root/piz/logs/20200330/test.log', Logger::WARNING));
		
		// add records to the log
//		$log->warning('Foo');
//		$log->error('Bar');
		
//		$config = new \EasySwoole\Mysqli\Config([
//		    'host'          => '127.0.0.1',
//		    'port'          => 3306,
//		    'user'          => 'root',
//		    'password'      => '@#yueG1G128...*',
//		    'database'      => 'ea888',
//		    'timeout'       => 5,
//		    'charset'       => 'utf8mb4',
//		]);
		
//		$client = new \EasySwoole\Mysqli\Client($config);
//		$client->queryBuilder()->get('users', 1);
//		var_dump($client->execBuilder());
//		var_dump($client);

//		echo $client->queryBuilder()->getLastPrepareQuery();
//		$builder = new QueryBuilder($client); //实际代码不用new,$client里面自动会实例化

//执行条件构造逻辑

//获取上次条件构造的预处理sql语句
//echo $builder->getLastPrepareQuery();
// SELECT  * FROM whereGet WHERE  col1 = ? 

//获取上次条件构造的sql语句
//echo $builder->getLastQuery();
//SELECT  * FROM whereGet WHERE  col1 = 2


    	$this->load->redis();
    	$name = $this->redis->get('name');
		var_dump($this->request->post);  //打印post 的数组
    	echo $name;

    	//打印加载的文件
//          $included_files = get_included_files();
//          foreach ($included_files as $filename) {
//              echo "$filename\n";
//          }
    	
//$pheanstalk = Pheanstalk::create('127.0.0.1');
//var_dump($pheanstalk);
//  	try{
//  		$conn = new \mysqli('mfamysql1.rwlb.rds.aliyuncs.com','jancojie','*$$)*!*(jancO','mfa_db_test',$port=3306);

			//测试load redis
//			$redis = $this->load->redis('redis1');  //redis1 是别名
//			$result = $this->redis1->get('cios:activity:rush:78');
//			$result = $redis->get('cios:activity:rush:78'); //或者这种写法也行,但没必要耗内存

			$this->load->model('mfa/TubMember', 'tubMember');
			var_dump($this->tubMember->getMemberInfo());return;
//  		$tubMember = new \app\models\mfa\TubMember();
//			var_dump($tubMember->getMemberInfo());return;
//			var_dump($this->server_root_route);
//			var_dump($this->server->setting); // 获取set设置的属性
//			var_dump($this->server->host); // 获取允许访问的ip
//			var_dump($this->server->port); // 获取开启的端口
//  		var_dump($this->uploadImage()); //上传图片
    		echo lalala(12345678).'<br/>';return;
			/*
			 *使用 ... 运算符（连接运算符），将 数组 和 可遍历 对象展开为函数参数，逆过来也可以将函数参数转成数组
			 */
			$operators = [2,3];
			var_dump($this->add(1, ...$operators));
			var_dump($this->handle('lalala','1','2'));
//  		hello();	
//  		$b = $a;
//			$a = new libs\a();  //异常捕获可以获取，set_error_handler 也可以获取，异常获取优先
//			throw new \Exception('错误调试', 400);  //抛出一个Exception,看是否可以被catch
//			\Piz\Log::get_instance()->set_server ($this->server)->write('WARNING',"参数类型必须为key=>val式的数组",json_encode($this->request->post),json_encode($this->request->get),'12335');
			var_dump($this->request->get['name']);  //获取get 的参数
			var_dump($this->request->post['token']); //获取post 的参数
			echo conf::NAME;
        	echo  date('Y-m-d H:i:s'),'<br/>';
        	echo __CLASS__;
			return;
//  	}catch (\Throwable $e) {
//  		echo $e->__toString();  //参考 https://www.cnblogs.com/wengyingfeng/p/9319883.html
//		}
//		catch (\Error $e)
//		{
// 			echo $e->__toString();
//		}

//  try {
//	throw new \Exception('错误调试', 400);  //抛出一个Exception,看是否可以被catch
//	echo conf::NAME;
//  hello();
//	} catch (\Throwable $e) {
//  	echo $e->getMessage();
//	}
        /*$user = new \app\model\User();
        $ret = $user->get_by_username ('ADMIN');
        var_dump ($ret);*/
//      hello();
//      var_dump($this->request->get['token']);  //获取get 的参数
//      var_dump($this->request->post['token']); //获取post 的参数
//      echo conf::NAME;
//      echo  date('Y-m-d h:I:s'),'<br/>';
//      echo __CLASS__;
    }

	
	public function handle($name,...$tip)   //长参数函数，可用于方法或者函数的不确定个数的参数，比如yy 的ecf 框架
	{
		return $tip;
	}
	
	public function add($a, $b, $c) {
    	return $a + $b + $c;
	}
}