<?php
//设置时区
date_default_timezone_set('Asia/Shanghai');
//定义框架路径
define ('PIZ_PATH',__DIR__.'/');
//Config文件目录
define ('CONFIG_PATH',dirname (__DIR__).'/config/');
//Logs路径
define('LOG_PATH',dirname (__DIR__).'/logs/');
//上传文件路径
define('STATIC_PATH',dirname (__DIR__).'/static/');
//助手函数的路径
define('MYHELPER_PATH',dirname (__DIR__).'/app/myHelpers/');
//引入加载器文件
require_once PIZ_PATH."Lib/Loader.php";
//引入助手文件,常用的函数
require_once PIZ_PATH."helper.php";
//注册自动加载
\Piz\Loader::register ();
//引入composer
require_once('/root/piz/vendor/autoload.php');
class start {
    /**
     * 配置参数 config/app.php
     * @var array
     */
    private static $config = null ;

    /**
     * frame/Lib/Server.php 实例
     * @var null
     */
    protected static $server = null ;

    public static function run($opt,$serverType,$port,$ip){   //如果用了 int 56dsd这类型字符串会强制转换成整型，不会报致命错误，加上类型限制有好也有坏
        if (version_compare(phpversion(), '7.1', '<')) {
            echo "PHP版本必须大于等于7.1 ，当前版本：",phpversion (),PHP_EOL;
            die;
        }

        if (version_compare(phpversion('swoole'), '2.1', '<')) {
            echo "Swoole 版本必须大于等于 2.1 ，当前版本：",phpversion ('swoole'),PHP_EOL;
            die;
        }
        if (php_sapi_name() != "cli") {
            echo "仅允许在命令行模式下运行",PHP_EOL;
            die;
        }
        //检查命令
        if(!in_array ($opt , ['start','stop','kill','restart','reload'])){
            echo PHP_EOL,"Usage:",PHP_EOL," php start.php [start|stop|kill|restart|reload]",PHP_EOL,PHP_EOL;
            die;
        }
		if($serverType && !in_array ($serverType , ['http','websocket'])){
			echo PHP_EOL,"Usage:",PHP_EOL," php start.php [start|stop|kill|restart|reload] [http|websocket]",PHP_EOL,PHP_EOL;
            die;
		}
		if($port && !is_numeric($port)){
			echo PHP_EOL,"Usage:",PHP_EOL," php start.php [start|stop|kill|restart|reload] [http|websocket] [正确的端口号] [正确的ip格式]",PHP_EOL,PHP_EOL;
            die;
		}
		if($ip && !preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$ip)){
			echo PHP_EOL,"Usage:",PHP_EOL," php start.php [start|stop|kill|restart|reload] [http|websocket] [正确的端口号] [正确的ip格式]",PHP_EOL,PHP_EOL;
            die;
		}

        self::$config = config('app');
        //注册项目命名空间和路径
        Piz\Loader::addNamespace (config('app.namespace'),config('app.path'));   //这里将命名空间的前缀app 对应的 /mnt/hgfs/centos7.6/php/piz/app/ 写入 Loader:namespaces 数组
        //检查日志目录是否存在并创建
        !is_dir(LOG_PATH) && mkdir(LOG_PATH,0777 ,TRUE);
        //检查是否配置app.name
        if(empty(self::$config['name'])){
            echo "配置项 config/app.php [name] 不可留空 ",PHP_EOL;
            die;
        }
		//生产进程的名字
		self::$config['server'] = $serverType = is_null($serverType)?self::$config['server']:$serverType;
		self::$config['port'] = $port = is_null($port)?self::$config['port']:$port;
		self::$config['ip'] = $ip = is_null($ip)?self::$config['ip']:$ip;
        $app_name = self::$config['name'] . '-' . $serverType . '-' . $port;

        //获取master_pid 关闭或重启时要用到
        $master_pid = exec("ps -ef | grep {$app_name}-master | grep -v 'grep ' | awk '{print $2}'");
        //获取manager_pid 重载时要用到
        $manager_pid = exec("ps -ef | grep {$app_name}-manager | grep -v 'grep ' | awk '{print $2}'");

        if (empty($master_pid)) {
            $master_is_alive = false;
        } else {
            $master_is_alive = true;
        }

        // if ($master_is_alive) {
        //     if ($opt === 'start' ) {
        //         echo "{$app_name}  正在运行" , PHP_EOL;
        //         exit;
        //     }
        // }else{
        //     elseif ($opt !== 'start' || $opt !== 'restart') {
        //         echo "{$app_name} 未运行" , PHP_EOL;
        //         exit;
        //     }
        // }       

        switch ($opt){
            case 'start':
                if ($master_is_alive) {
                    echo "{$app_name}  正在运行" , PHP_EOL;
                    exit;
                }
				echo "正在启动 ..." , PHP_EOL;
                break;
            case "kill":
                //代码参考 https://wiki.swoole.com/wiki/page/233.html
                exec("ps -ef|grep {$app_name}|grep -v grep|cut -c 9-15|xargs kill -9");
                break;

            case 'stop':
                if (!$master_is_alive) {
                    echo "{$app_name} 未运行" , PHP_EOL;
                    exit;
                }
                echo "{$app_name}  正在停止 ..." , PHP_EOL;
                // 发送SIGTERM信号，主进程收到SIGTERM信号时将停止fork新进程，并kill所有正在运行的工作进程
                // 详见 https://wiki.swoole.com/wiki/page/908.html
                $master_pid && posix_kill($master_pid, SIGTERM);
                // Timeout.
                $timeout = 100;
                $start_time = time();

                while (1) {                           //强制退出
                    $master_is_alive = $master_pid && posix_kill($master_pid, 0);
                    if ($master_is_alive) {
                        if (time() - $start_time >= $timeout) {
                            echo "{$app_name} 停止失败" , PHP_EOL;
                            exit;
                        }
//                      usleep(10000);
                        continue;
                    }
                    echo "{$app_name} 已停止" , PHP_EOL;
                    break;
                }
                exit(0);
                break;
            case 'reload':
                if (!$master_is_alive) {
                    echo "{$app_name} 未运行" , PHP_EOL;
                    exit;
                }
                //详见：https://wiki.swoole.com/wiki/page/20.html
                // SIGUSR1: 向主进程/管理进程发送SIGUSR1信号，将平稳地restart所有worker进程
                posix_kill($manager_pid, SIGUSR1);
                echo "[SYS]","\t", "{$app_name} 重载" , PHP_EOL;
                exit;
            case 'restart':
                if (!$master_is_alive) {
                    echo "{$app_name} 未运行" , PHP_EOL;
                    echo "正在启动 ..." , PHP_EOL;
                    break;
                }
                echo "{$app_name} 正在停止" , PHP_EOL;
                // 发送SIGTERM信号，主进程收到SIGTERM信号时将停止fork新进程，并kill所有正在运行的工作进程
                // 详见 https://wiki.swoole.com/wiki/page/908.html
                $master_pid && posix_kill($master_pid, SIGTERM);
                $timeout = 40;
                $start_time = time();
                while (1) {
                                                      //检查master_pid是否存在
                    $master_is_alive = $master_pid && posix_kill($master_pid, 0);
                    if ($master_is_alive) {
                        if (time() - $start_time >= $timeout) {
                            echo "{$app_name} 停止失败" , PHP_EOL;
                            exit;
                        }
//                      usleep(10000);
                        continue;
                    }
                    echo "{$app_name} 已停止" , PHP_EOL;
                    break;
                }

                break;
        }
		//由于开启和重启都用到，所以放到下面位置
        self::$server = \Piz\Server::get_instance ();
		self::$server->set_config (self::$config);
		//设置具体的服务信息
		self::$server->set_server_type ($serverType);
		self::$server->set_port($port);
		self::$server->set_ip($ip);
		echo "{$app_name}", ' 启动成功',PHP_EOL;
        self::$server->run();
		
    }
}

