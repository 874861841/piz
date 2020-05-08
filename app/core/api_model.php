<?php
namespace app\core;
class api_model extends \Piz\Model
{
	public function __construct()
	{
		parent::__construct();
		//设置连接的数据库
		$this->setConfigAndConn('kingshan');
	}
}