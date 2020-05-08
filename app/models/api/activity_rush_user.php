<?php
namespace app\models\api;
class activity_rush_user extends \app\core\api_model
{
	public function __construct() {
		parent::__construct();
		$this->setTableName('activity_rush_user');
//		$this->setDbName('mfa_db_test');  //这样设置数据库 会非常慢
	}
	
	public function getMemberInfo() {
		
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