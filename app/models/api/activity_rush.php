<?php
namespace app\models\api;
class activity_rush extends \app\core\api_model
{
	public function __construct() {
		parent::__construct();
		$this->setTableName('activity_rush');
//		$this->setDbName('mfa_db_test');  //这样设置数据库 会非常慢
	}
	
}