<?php
declare(strict_types=1);
namespace app\controllers\api;
use app\controllers\api\lib\conf;
use Swoole\Coroutine;
use Swoole\Database\MysqliConfig;
use Swoole\Database\MysqliPool;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

const N = 10;
class index extends \app\core\api_controller
{
//	public function __destruct() {
//      echo 'byby';
//  }
    public function tellName(){
    	try{

		//\Swoole\Runtime::enableCoroutine(); //一键协程化处理
		
		//redis普通的单例模式
			$this->load->redis();
			echo $this->redis->get('foo0');
		//redis连接池操作
			$this->load->redisPool();
			$pool = $this->redisPool;
			$redis = $pool->get();
			$redis->set('foo', 'bar');
			echo $redis->get('foo0');
			$pool->put($redis);	

			\Swoole\Runtime::enableCoroutine();
    		$wg = new \Swoole\Coroutine\WaitGroup();
		    $result = [];
		    $wg->add();
		    //启动第一个协程
		    go(function () use ($wg, &$result) {
//		    	echo $this->redis->get('foo0'); //可以直接使用$this不用传参方式
		        //启动一个协程客户端client，请求淘宝首页
		        sleep(1);
		        $result['taobao'] = 1;
		        $wg->done();
		    });
		
		    $wg->add();
		    //启动第二个协程
		    go(function () use ($wg, &$result) {
		        //启动一个协程客户端client，请求百度首页
			    sleep(2);
		        $result['baidu'] = 2;
		        $wg->done();
		    });
		
		    //挂起当前协程，等待所有任务完成后恢复
		    $wg->wait();
		    //这里 $result 包含了 2 个任务执行结果
		    var_dump($result);
    		echo microtime().PHP_EOL;
			echo microtime().PHP_EOL;
			echo time().PHP_EOL;
            echo date("Y-m-d H:i:s",time());
            $this->load->model('api/users', 'users');
            $userArr = $this->users->makeSqlSelect("*")->makeSqlWhere('id',2222)->queryOne(TRUE);    
    		$this->echoJson(1,$userArr,'成功',1);
            //打印加载的文件
            // $included_files = get_included_files();
            // foreach ($included_files as $filename) {
            //     echo "$filename\n";
            // }
            echo date("Y-m-d H:i:s").PHP_EOL;
    		echo lalalaapi(12345678).'<br/>'; //测试用一个单例模式Initialize引入自定义函数
			return;
    	}catch (\Throwable $e) {
            throw new \Exception($e->__toString(), 400);  //抛出一个Exception,可以被catch
    		// echo $e->__toString();  //参考 https://www.cnblogs.com/wengyingfeng/p/9319883.html
		}
		catch (\Error $e)
		{
   			echo $e->__toString();
		}
    }
	
}