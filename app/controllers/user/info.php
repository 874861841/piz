<?php
namespace app\controllers\user;
class info extends \Piz\WsController
{
	//异步的方式返回到前端
    public function get(){
        $content = "FD:{$this->fd};say:{$this->param['msg']}";
        $this->task->delivery (\app\task\SendMsg::class,'ToAll',[$this->fd,$content]); //投递异步任务
    }
}