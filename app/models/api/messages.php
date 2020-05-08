<?php

/**
 * @author: zwy<874861841@qq.com>
 * @e-mail: 874861841@qq.com
 * @date:   2020-01-09 18:06:38
 * @last modified by:   zwy<874861841@qq.com>
 * @last modified time: 2020-01-09 18:08:19
 */
namespace app\models\api;
class messages extends \app\core\api_model
{
	public function __construct() {
		parent::__construct();
		$this->setTableName('messages');
//		$this->setDbName('mfa_db_test');  //这样设置数据库 会非常慢
	}
	
}