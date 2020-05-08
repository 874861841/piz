<?php
/**
 * 通知任务类
 */
namespace app\task;
class WriteLog{
    /**
     * 写入日志，可延迟写入
     * @param $type     日志的类型
     * @param $msg      日志的内容
	 * @param $ms       延迟的时间，写日志不应该用延迟任务，这里是做延迟任务的示范
     *
     * @return bool
     */
    public function Write($type,$msg,$ms = 3000){
//  	swoole_timer_after($ms, function ()use($type,$msg,$ms){
//	    	echo "after $ms.\n";
			$dir_path = LOG_PATH.date('Ymd').DIRECTORY_SEPARATOR;
	        !is_dir($dir_path) && mkdir($dir_path,0777,TRUE);
	        $filename  = date("H").'.'.$type.'.log';
			file_put_contents($dir_path.$filename,$msg.PHP_EOL,FILE_APPEND);
//		});    	
	}
}