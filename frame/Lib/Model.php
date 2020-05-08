<?php
/**
 * Model
 */
namespace Piz;

class Model
{
	//数据库配置
    protected $conn;
    //数据库配置
    protected $config = [];
    //库名
    protected $dbName = '';

    //数据表名
    protected $tableName = '';
	
	protected $sqlSelect = '';
	
	protected $sqlJoin = '';
	
	protected $sqlSelectWhere = '';
	
	protected $sqlDeleteWhere = '';
	
	protected $sqlUpdateWhere = '';
	
	protected $sqlSelectLimit = '';
	
	protected $sqlSelectOrderBy = '';
	
	protected $sqlSelectGroupBy = '';
	
	public $lastSql = '';
	
	

    public function __construct() {
    	
	}
	
	protected function setConfigAndConn($value)
	{
		$this->config = config('database.'.$value);
		$this->dbName = $this->config['database'];
		//单例连接方式
//		$this->conn = \Piz\Db\Mysqli::get_instance($this->config);
		//连接池方式
		$this->pool = \Piz\Db\MysqliPool::get_instance($this->config);
		$this->conn = $this->pool->get();
		/* 检查连接是否还活跃 */
//		if ($this->conn->ping()) {
//		    printf ("Our connection is ok!\n");
//		} else {
//		    printf ("Error: %s\n", $this->conn->error);
//		}
	}
	
	protected function setDbName($value)
	{
		$this->dbName = $value;
		mysqli_select_db($this->conn,$this->dbName);
	}
	
	protected function setTableName($value)
	{
		$this->tableName = $value;
		return $this;
	}

	## 返回插入的ID
    public function insert($arr_data,$clean = TRUE){
    	$conn = $this->conn;
    	$table = $this->tableName;
        $str_dataKey = $str_dataValue = '';
        foreach($arr_data as $key => $value){
            if(empty($key) || empty($value)){
                continue;
            }
            ##安全编码处理
            if(!is_integer($value)){
                $value = "'".$conn->escape_string($value)."'";
            }
            $str_dataKey = "{$str_dataKey} `{$key}`,";
            $str_dataValue = "{$str_dataValue} {$value},";
        }
        $str_dataKey = trim($str_dataKey," \t\n\r\0\x0B,");
        $str_dataValue = trim($str_dataValue," \t\n\r\0\x0B,");
        $sql = "insert into `{$table}` ({$str_dataKey}) values ({$str_dataValue})";
		
		$mysqli_result = $conn->query($sql);
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql);
			throw new \Exception("执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
        return $conn->insert_id;
    }
    
    ## 返回删除影响的条目数
    public function delete($deleteWhereArr,$clean = TRUE){
    	$conn = $this->conn;
    	$table = $this->tableName;
		
		foreach ($deleteWhereArr as $key=>$val) {
        	if($this->sqlDeleteWhere){
        		$this->sqlDeleteWhere .= " and `$key` = '$val' ";            		
        	}else{
        		$this->sqlDeleteWhere .= " where `$key` = '$val' ";
        	}
        }        
        $sql = "delete from `{$table}` $this->sqlDeleteWhere";       
        $mysqli_result = $conn->query($sql);
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql);
			throw new \Exception("执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
        return $conn->affected_rows;
    }


	## 返回更新影响的条目数
    public function update($updateWhereArr,$arr_data,$clean = TRUE){
    	$conn = $this->conn;
    	$table = $this->tableName;
		
		foreach ($updateWhereArr as $key=>$val) {
        	if($this->sqlUpdateWhere){
        		$this->sqlUpdateWhere .= " and `$key` = '$val' ";            		
        	}else{
        		$this->sqlUpdateWhere .= " where `$key` = '$val' ";
        	}
        }
		
        $str_data = '';
        foreach($arr_data as $key => $value){
            if(empty($key)){
                continue;
            }
			//不是整形也不是null 的做处理(对字符串做处理)
            if(!is_integer($value) && !is_null($value)){
                $value = "'".$conn->escape_string($value)."'";
            }
            if(NULL === $value){
                $str_data = "{$str_data} `{$key}` = NULL,";
            }else{
                $str_data = "{$str_data} `{$key}` = {$value},";
            }
        }
        $str_data = trim($str_data," \t\n\r\0\x0B,");
        $sql = "update `{$table}` set {$str_data} {$this->sqlUpdateWhere}";
		$mysqli_result = $conn->query($sql);
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql);
			throw new \Exception("执行sql的时候失败, 错误信息为： \n".$conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
        return $conn->affected_rows;
        
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
        return $this->conn->select($data, $this->table_name, $where, $limit, $order, $group, $key);
    }

    /**
     * 获取最后执行SQL
     * @return mixed
     */
    final public function get_last_query(){
        return $this->conn->get_last_query();
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
        return $this->conn->get_one($data, $this->table_name, $where, $order, $group);
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
//  final public function query($sql) {
//      return $this->conn->query($sql);
//  }

    /**
     * 执行添加记录操作
     * @param array $data 		要增加的数据，参数为数组。数组key为字段值，数组值为数据取值
     * @param bool $return_insert_id 是否返回新建ID号
     * @param bool $replace 是否采用 replace into的方式添加数据
     * @return boolean|int
     */
//  final public function insert($data, $return_insert_id = false, $replace = false) {
//      return $this->conn->insert($data, $this->table_name, $return_insert_id, $replace);
//  }

    /**
     * 获取最后一次添加记录的主键号
     * @return int
     */
    final public function insert_id() {
        return $this->conn->insert_id();
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
//  final public function update($data, $where = '') {
//      if (is_array($where)) $where = $this->sqls($where);
//      return $this->conn->update($data, $this->table_name, $where);
//  }

    /**
     * 执行删除记录操作
     * @param array|string $where 		删除数据条件,不充许为空。
     * @return boolean
     */
//  final public function delete($where) {
//      if (is_array($where)) $where = $this->sqls($where);
//      return $this->conn->delete($this->table_name, $where);
//  }

 	protected function checkParams($params){
        if( isset($params) && "" !== $params ){
            return true;
        }
        return false;
    }

    ###queryType（fuzzy：模糊查询|accurate：精确查询）
    public function publicOr(string $fields,string $fieldsValue,string $queryType){
        $arrFields = explode(',',$fields);
        $arrFieldsValue = explode(',',$fieldsValue);
        $sqlWhereTemp = '';
        $sqlWhereOrTemp = '';
        $sqlWhereTemp .= " and (";
        if('accurate' === $queryType){
            foreach ($arrFieldsValue as $FieldsValue_key => $FieldsValue_value) {
                $searchstr = '';
                foreach ($arrFields as $Fields_key => $Fields_value) {
                   if('' !== $FieldsValue_value) $searchstr.="or `{$Fields_value}` = '{$FieldsValue_value}' ";
                }
                $sqlWhereOrTemp .= $searchstr;
            }
        }else{          
            foreach ($arrFieldsValue as $FieldsValue_key => $FieldsValue_value) {
                foreach ($arrFields as $Fields_key => $Fields_value) {
                   if('' !== $FieldsValue_value) $searchstr.="or `{$Fields_value}` like '%{$FieldsValue_value}%' ";
                }
                $sqlWhereOrTemp .= $searchstr;
            }
        } 
        $sqlWhereOrTemp = trim($sqlWhereOrTemp,'or');
        $sqlWhereTemp .= $sqlWhereOrTemp.")"; 
        return $sqlWhereTemp;
    }

 	public function makeSqlSelect(string $sqlSelect){ 		
 		$this->sqlSelect = "select ".$sqlSelect." from $this->tableName";
		return $this;
    }
	
	
	public function makeSqlJoin(string $table,string $condition,string $joinType = "left join"){
 		$this->sqlJoin .= ' '.$joinType.' '.$table." on ".$condition;
		return $this;
    }
	
	/**
     * where 单字段语句组合成SQL语句
     * @param $whereKey 查新的字段
     * @param $whereVal 查新的字段的值
     *
     * @return string
     */
    public function makeSqlWhere($paramFirst,$paramSecond = '') {
    	$comepareArr = ['<','>','='];
		$pattern = '/('.implode('|', $comepareArr).')$/';
    	if(is_array($paramFirst)){
    		//填数组的方式
	    	foreach ($paramFirst as $key=>$val) {
	    		if (preg_match($pattern, $key)) {
	            	if($this->sqlSelectWhere){
		        		$this->sqlSelectWhere .= " and $key '{$val}' ";            		
		        	}else{
		        		$this->sqlSelectWhere .= " where $key '{$val}' ";
		        	}
        		}else {
        			if($this->sqlSelectWhere){
        				if($val === 'is null' || $val === 'is not null'){
        					$this->sqlSelectWhere .= " and $key $val ";
        				}else {
        					$this->sqlSelectWhere .= " and $key = '{$val}' ";
        				}		            		
		        	}else{
		        		if($val === 'is null' || $val === 'is not null'){
        					$this->sqlSelectWhere .= " where $key $val ";
        				}else {
        					$this->sqlSelectWhere .= " where $key = '{$val}' ";
        				}
		        	}
        		}
				
	        }
    	}else {
    		//字符串的方式
    		if (preg_match($pattern, $paramFirst)) {
            	if($this->sqlSelectWhere){
	        		$this->sqlSelectWhere .= " and $paramFirst '$paramSecond' ";            		
	        	}else{
	        		$this->sqlSelectWhere .= " where $paramFirst '$paramSecond' ";
	        	}
        	}else {
	        	if($this->sqlSelectWhere){
		    		$this->sqlSelectWhere .= " and `$paramFirst` = '$paramSecond' ";
		    	}else{
					$this->sqlSelectWhere .= " where `$paramFirst` = '$paramSecond' ";
		    	}
        	}
    	}    	
				
        return $this;
    }

    public function makeSqlLimit($limitStart,$limitCount){
        if($this->checkParams($limitStart) && $this->checkParams($limitCount)){
            $this->sqlSelectLimit .= ' limit '.$limitStart.','.$limitCount;
        }
		return $this;
    }

    public function makeSqlOrderBy(array $param = array()){
		foreach ($param as $key => $value) {
			if($this->checkParams($key) && $this->checkParams($value)){				
            	$this->sqlSelectOrderBy .= $this->sqlSelectOrderBy?','.$key.' '.$value:' order by '.$key.' '.$value;
        	}
		}       
		return $this;
    }
	
    public function makeSqlGroupBy($groupFields){
    	if(is_array($groupFields)){
    		//填数组的方式
    		foreach ($groupFields as $val) {
	        	if($this->sqlSelectWhere){
	        		$this->sqlSelectWhere .= ",$val ";            		
	        	}else{
	        		$this->sqlSelectWhere .= ' group by '.$val;
	        	}
        	}
    	}else {
    		//填字符串的方式
	        if($this->checkParams($groupFields)){
	            $this->sqlSelectGroupBy .= ' group by '.$groupFields;
	        }		
    	}
		return $this;
    }
	
    //FALSE 的用意是queryCount和query 里面的sqlSelect等的互相复用
	public function queryCount($clean = FALSE){
	 	$sql = $this->sqlSelect.$this->sqlJoin.$this->sqlSelectWhere.$this->sqlSelectGroupBy;
		$mysqli_result = $this->conn->query($sql);  //允许sql 后的结果结果集， 结果集里面的方法参考 https://www.runoob.com/php/php-ref-mysqli.html
		$this->lastSql = $sql;
		$this->mysqliResult = $mysqli_result;
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql);
			throw new \Exception("执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
		return $mysqli_result->num_rows;
    }
	
	public function query($clean = FALSE){
	 	$sql = $this->sqlSelect.$this->sqlJoin.$this->sqlSelectWhere.$this->sqlSelectGroupBy.$this->sqlSelectOrderBy.$this->sqlSelectLimit;
		$mysqli_result = $this->conn->query($sql);  //允许sql 后的结果结果集， 结果集里面的方法参考 https://www.runoob.com/php/php-ref-mysqli.html
		$this->lastSql = $sql;
		$this->mysqliResult = $mysqli_result;
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql);
			throw new \Exception("执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
		return $mysqli_result->fetch_all(MYSQLI_ASSOC);
    }
	
	public function queryOne($clean = TRUE){
	 	$sql = $this->sqlSelect.$this->sqlJoin.$this->sqlSelectWhere.$this->sqlSelectGroupBy.$this->sqlSelectOrderBy.' limit 1';
		$mysqli_result = $this->conn->query($sql);  //允许sql 后的结果结果集， 结果集里面的方法参考 https://www.runoob.com/php/php-ref-mysqli.html
		$this->lastSql = $sql;
		$this->mysqliResult = $mysqli_result;
		if(false === $mysqli_result){
			$this->redis = \Piz\Redis::get_instance();
			$this->redis->lpush('sqlerror',"执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql);
			// throw new \Exception("执行sql的时候失败, 错误信息为： \n".$this->conn->error."</br> 错误sql: \n".$sql, 400);
		}
		if(TRUE === $clean){
			$this->clean();
		}
		return $mysqli_result->fetch_assoc();  //获取一条数据
    }
	
	##用于两个字段才能确定一条数据，导出时多选需传入数组
	public function makeSqlWhereVariousParams(array $param = array()){
		if(empty($param)) return ;
		$sqlWhereTemp = " and (";
		$searchstr = '';
		foreach ($param as $key => $value) {
			foreach ($value as $k => $v) {
				$searchstr.= " `{$k}` = '{$v}' and";
			}
			$searchstr = trim($searchstr,'and');
			$searchstr.=" or"; 
		}
		$searchstr = trim($searchstr,'or');
		$sqlWhereTemp .= $searchstr;
        $sqlWhereTemp .= $sqlWhereOrTemp.")"; 
        return $sqlWhereTemp;
    }
	
	public function clean(){
		$this->sqlSelect = $this->sqlJoin = $this->sqlSelectWhere = $this->sqlSelectGroupBy = $this->sqlSelectOrderBy = $this->sqlSelectLimit = 
		$this->sqlDeleteWhere = $this->sqlUpdateWhere = '';
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
                $sql .= $sql ? " $font `$key` = '$val' " : " where `$key` = '$val'";
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
        return $this->conn->affected_rows();
    }

    /**
     * 事务开始
     */
    final public function begin( ){
        return $this->conn->begin();
    }
    /**
     * 事务回滚
     */
    final public function rollback(){
        return $this->conn->rollback();

    }
    /**
     * 事务确认
     */
    final public function commit(){
        return $this->conn->commit();
    }

    /**
     * 获取数据表主键
     * @return array
     */
    final public function get_primary() {
        return $this->conn->get_primary($this->table_name);
    }

    /**
     * 返回数据结果集
     * @return array
     */
    final public function fetch_array() {
        $data = array();
        while($r = $this->conn->fetch_next()) {
            $data[] = $r;
        }
        return $data;
    }
    /**
     * 返回数据库版本号
     */
    final public function version() {
        return $this->conn->version();
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
	
	public function __destruct() {
        $this->pool->put($this->conn);
    }
}