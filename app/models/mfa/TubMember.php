<?php
namespace app\models\mfa;
class TubMember extends \app\core\mfa_model
{
	public function __construct() {
		parent::__construct();
		$this->setTableName('t_hd3_receive');
//		$this->setDbName('mfa_db_test');  //这样设置数据库 会非常慢
	}
	
	public function getMemberInfo() {
		$this->makeSqlSelect("count(*),Member_ID,ID");
		$this->makeSqlJoin('t_ub_member','t_hd3_receive.Member_ID = t_ub_member.ID','left join');  //开始位置和每页条数
//		$this->makeSqlWhere(['Member_ID'=>'27736','ID'=>'27736']);
//		$this->makeSqlWhere('Member_ID','27736');  //开始位置和每页条数 ,也可以填数组的方式 ['Member_ID'=>'27736','ID'=>'27736']		
		$this->makeSqlLimit('0','10');  //开始位置和每页条数
		$this->makeSqlGroupBy('Member_ID,ID');  //开始位置和每页条数,也可以填数组 ['Member_ID','ID']
		$this->makeSqlOrderBy(['ID'=>'DESC','InputDate'=>'ASC']);  //开始位置和每页条数
//		$list = $this->query();  //加多一个TRUE 执行后清楚参数设置
		$count = $this->queryCount(TRUE);  //加多一个TRUE 执行后清楚参数设置
//		$this->conn -> close();  //使用后会导致单例连接关闭
//		$listPlus = $this->makeSqlSelect("Member_ID as memberId,Total")->makeSqlWhere('Member_ID','27736')->query();
//		$listPlus = $this->setTableName('t_ub_member')->makeSqlSelect("ID as id,Phone")->makeSqlWhere('ID','27736')->query(); //基本不会换表使用，因为一个模型一个表
		echo $this->lastSql.'</br>';
		return $count;
		$sql = "select * from $this->tableName limit 10";  //直接写sql 的方法，如果有嵌套的还是建议写sql
//		var_dump($this->conn->query($sql)->fetch_all(MYSQLI_ASSOC));
//		$this->conn -> close();
		return $data;
	}

	public function insertData() {
		return $this->insert(['Type1'=>1000,'Type2'=>1000]);
	}
	
	public function deleteData() {
		return $this->delete(['ID'=>24]);
	}
	
	public function updateData() {
		return $this->update(['ID'=>24],['Type1'=>'10012','Type2'=>1001]);
	}
	
}