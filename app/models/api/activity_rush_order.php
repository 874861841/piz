<?php
namespace app\models\api;
class activity_rush_order extends \app\core\api_model
{
	public function __construct() {
		parent::__construct();
		$this->setTableName('activity_rush_order');
//		$this->setDbName('mfa_db_test');  //这样设置数据库 会非常慢
	}
	
	public function getMemberInfo() {
		
	}

	public function insertData() {
		return $this->insert(['rush_id'=>100]);
	}
	
	public function deleteData() {
		return $this->delete(['ID'=>24]);
	}
	
	public function updateData() {
		return $this->update(['ID'=>24],['Type1'=>'10012','Type2'=>1001]);
	}
	
}