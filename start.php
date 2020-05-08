<?php
//var_dump($argv);exit;
require "./frame/base.php";
//起飞起飞
start::run(isset($argv[1]) ? $argv[1] : NULL,isset($argv[2]) ? $argv[2] : NULL,isset($argv[3]) ? $argv[3] : NULL,isset($argv[4]) ? $argv[4] : NULL);
