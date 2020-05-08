<?php
/**
 * Model
 */

namespace Piz;

class Model
{
    //数据库配置
    protected $config = [];
    //数据库连接
    protected $db = '';
    //调用数据库的配置项
    protected $setting = 'default';
    //数据表名
    protected $table_name = '';

    public function __construct() {
        $this->config = Config::get_instance()->get('database');
        if(!isset($this->config[$this->setting])) $this->setting='default';
        $this->db = DbFactory::get_factory($this->config)->get_database($this->setting);
    }

    /**
     * 执行sql查询
     * @param string $where 查询条件[例`name`='$name']
     * @param string $data  需要查询的字段值[例`name`,`gender`,`birthday`]
     * @param string $limit 返回结果范围[例：10或10,10 默认为空]
     * @param string $order 排序方式	[默认按数据库默认方式排序]
     * @param string $group 分组方式	[默认为空]
     * @param string $key   返回数组按键名排序
     *
     * @return mixed        查询结果集数组
     */
    final public function select($where = '', $data = '*', $limit = '', $order = '', $group = '', $key='') {
        if (is_array($where)) $where = $this->sqls($where);
        return $this->db->select($data, $this->table_name, $where, $limit, $order, $group, $key);
    }

    /**
     * 获取最后执行SQL
     * @return mixed
     */
    final public function get_last_query(){
        return $this->db->get_last_query();
    }

    /**
     * 获取单条记录查询
     * @param string $where 查询条件
     * @param string $data 	需要查询的字段值[例`name`,`gender`,`birthday`]
     * @param string $order 排序方式	[默认按数据库默认方式排序]
     * @param string $group 分组方式	[默认为空]
     * @return mixed	    数据查询结果集,如果不存在，则返回空
     */
    final public function get_one($where = '', $data = '*', $order = '', $group = '') {
        if (is_array($where)) $where = $this->sqls($where);
        return $this->db->get_one($data, $this->table_name, $where, $order, $group);
    }

    /**
     * 计算记录数
     * @param string|array $where 查询条件
     *
     * @return mixed
     */
    final public function count($where = '') {
        $r = $this->get_one($where, "COUNT(*) AS num");
        return $r['num'];
    }
    /**
     * 直接执行sql查询
     * @param string $sql	查询sql语句
     * @return boolean/query resource 如果为查询语句，返回资源句柄，否则返回true/false
     */
    final public function query($sql) {
        return $this->db->query($sql);
    }

    /**
     * 执行添加记录操作
     * @param array $data 		要增加的数据，参数为数组。数组key为字段值，数组值为数据取值
     * @param bool $return_insert_id 是否返回新建ID号
     * @param bool $replace 是否采用 replace into的方式添加数据
     * @return boolean|int
     */
    final public function insert($data, $return_insert_id = false, $replace = false) {
        return $this->db->insert($data, $this->table_name, $return_insert_id, $replace);
    }

    /**
     * 获取最后一次添加记录的主键号
     * @return int
     */
    final public function insert_id() {
        return $this->db->insert_id();
    }

    /**
     * 执行更新记录操作
     * @param array $data 		要更新的数据内容，参数可以为数组也可以为字符串，建议数组。
     * 						为数组时数组key为字段值，数组值为数据取值
     * 						为字符串时[例：`name`='phpcms',`hits`=`hits`+1]。
     *						为数组时[例: array('name'=>'phpcms','password'=>'123456')]
     *						数组的另一种使用array('name'=>'+=1', 'base'=>'-=1');程序会自动解析为`name` = `name` + 1, `base` = `base` - 1
     * @param string|array $where 		更新数据时的条件,可为数组或字符串
     * @return boolean
     */
    final public function update($data, $where = '') {
        if (is_array($where)) $where = $this->sqls($where);
        return $this->db->update($data, $this->table_name, $where);
    }

    /**
     * 执行删除记录操作
     * @param array|string $where 		删除数据条件,不充许为空。
     * @return boolean
     */
    final public function delete($where) {
        if (is_array($where)) $where = $this->sqls($where);
        return $this->db->delete($this->table_name, $where);
    }

    /**
     * 将数组转换为SQL语句
     * @param string|array $where 要生成的数组
     * @param string $font        连接串。
     *
     * @return string
     */
    final public function sqls($where, $font = ' AND ') {
        if (is_array($where)) {
            $sql = '';
            foreach ($where as $key=>$val) {
                $sql .= $sql ? " $font `$key` = '$val' " : " `$key` = '$val'";
            }
            return $sql;
        } else {
            return $where;
        }
    }

    /**
     * 获取最后数据库操作影响到的条数
     * @return int
     */
    final public function affected_rows() {
        return $this->db->affected_rows();
    }

    /**
     * 事务开始
     */
    final public function begin( ){
        return $this->db->begin();
    }
    /**
     * 事务回滚
     */
    final public function rollback(){
        return $this->db->rollback();

    }
    /**
     * 事务确认
     */
    final public function commit(){
        return $this->db->commit();
    }

    /**
     * 获取数据表主键
     * @return array
     */
    final public function get_primary() {
        return $this->db->get_primary($this->table_name);
    }

    /**
     * 返回数据结果集
     * @return array
     */
    final public function fetch_array() {
        $data = array();
        while($r = $this->db->fetch_next()) {
            $data[] = $r;
        }
        return $data;
    }
    /**
     * 返回数据库版本号
     */
    final public function version() {
        return $this->db->version();
    }

    /**
     * 生成sql语句，如果传入$in_cloumn 生成格式为 IN('a', 'b', 'c')
     * @param  array $data 条件数组或者字符串
     * @param string $front 连接符
     * @param bool   $in_column 字段名称
     * @return string
     */
    final public function to_sqls($data, $front = ' AND ', $in_column = false) {
        if($in_column && is_array($data)) {
            $ids = '\''.implode('\',\'', $data).'\'';
            $sql = "$in_column IN ($ids)";
            return $sql;
        } else {
            if ($front == '') {
                $front = ' AND ';
            }
            if(is_array($data) && count($data) > 0) {
                $sql = '';
                foreach ($data as $key => $val) {
                    $sql .= $sql ? " $front `$key` = '$val' " : " `$key` = '$val' ";
                }
                return $sql;
            } else {
                return $data;
            }
        }
    }
}