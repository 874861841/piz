<?php
/**
 *  数据库工厂类
 */
namespace Piz;

final class DbFactory {
    /**
     * 当前数据库工厂类静态实例
     */
    private static $factory;

    /**
     * 数据库配置列表
     */
    protected $config = [];

    /**
     * 数据库操作实例化列表
     */
    protected $dbs = [] ;
    /**
     * 构造函数
     */
    private function __construct (){}
    private function __clone() {}

    /**
     * 返回当前终级类对象的实例
     * @param $db_config 数据库配置
     * @return object
     */
    public static function get_factory($config = [] ) {
        if( is_null(self::$factory) ) {
            self::$factory = new self;
            self::$factory->config = $config ;
        }
        return self::$factory;
    }

    /**
     * 获取数据库操作实例
     * @param $db_name 数据库配置名称
     */
    public function get_database($setting) {
        if(!isset($this->dbs[$setting]) || !is_object($this->dbs[$setting])) {
            $this->dbs[$setting] = $this->connect($setting);
        }
        return $this->dbs[$setting];
    }

    /**
     *  加载数据库驱动
     * @param $db_name 	数据库配置名称
     * @return object
     */
    public function connect($setting) {
        //可在此加载其它实现类驱动
        $object = new Db\Pdo();
        $object->open($this->config[$setting]);
        return $object;
    }

    /**
     * 关闭数据库连接
     * @return void
     */
    protected function close() {
        foreach($this->dbs as $db) {
            $db->close();
        }
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        $this->close();
    }
}


