<?php
/**
 * 助手函数
 */

/**
 * 获取实例
 * @param $class
 * @return mixed
 */
function get_instance($class){
    return ($class)::get_instance();
}
/**
 * 获取配置参数
 * @param      $name        参数名 格式：文件名.参数名
 * @param null $default     错误默认返回值
 *
 * @return mixed|null
 */
function config($name,$default = NULL){
    return get_instance('\Piz\Config')->get($name,$default);
}

/**
 * 写入日志
 * @param       $type       EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG
 * @param array ...$log     标量参数，可多个
 */
function logs($type,...$log){
    get_instance('\Piz\Log')->write($type,...$log);
}


function weiyu(){
    return 'zhongweiyu';
}