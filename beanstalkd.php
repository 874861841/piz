<?php
//创建队列消息
require_once('/root/piz/vendor/autoload.php');
use Pheanstalk\Pheanstalk;
$pheanstalk = Pheanstalk::create('127.0.0.1');
var_dump($pheanstalk);
