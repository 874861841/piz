<?php
namespace app\modules\user;
class info extends \Piz\WsController
{
    public function get(){
        $content = "FD:{$this->fd};say:{$this->param['msg']}";
        $this->task->delivery (\app\task\Notice::class,'ToAll',[$this->fd,$content]);
    }
}