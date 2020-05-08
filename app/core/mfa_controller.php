<?php
namespace app\core;
class mfa_controller extends \Piz\Controller
{
	public function __construct($server,$request,$response,$router)
	{
		parent::__construct($server,$request,$response,$router);
		//接下来可以做一些token 验证等的初始化处理
	}
}