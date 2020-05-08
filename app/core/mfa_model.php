<?php
namespace app\core;
class mfa_model extends \Piz\Model
{
	public function __construct()
	{
		parent::__construct();
		//设置连接
		$this->setConfigAndConn('default');
	}
}