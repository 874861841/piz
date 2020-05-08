<?php
 
class Ws {
    public $ws = null;
    public function __construct() {
        $this->ws = new swoole_websocket_server("0.0.0.0", 9501);
        $this->ws->set([
                'worker_num' => 2, //worker进程数
                'task_worker_num' => 2, //task进程数
            ]);
        $this->ws->on("open", [$this, 'onOpen']);
        $this->ws->on("message", [$this, 'onMessage']);
        $this->ws->on("task", [$this, 'onTask']);
        $this->ws->on("finish", [$this, 'onFinish']);
        $this->ws->on("close", [$this, 'onClose']);
        $this->ws->start();
    }
 
    //建立连接回调
    public function onOpen($ws, $request) {
        echo "{$request->fd}建立了连接";
    }
 
    //接受消息回调
    public function onMessage($ws, $frame) {
        //worker进程异步投递任务到task_worker进程中
        $data = [
            'fd' => $frame->fd,
        ];
        $ws->task($data);
 
        //服务器返回
        echo "服务器发送消息:666";
    }
 
    //完成异步任务回调
    public function onTask($serv, $task_id, $worker_id, $data) {
        var_dump($data);
 
        //模拟慢速任务
        sleep(5);
 
        //返回字符串给worker进程——>触发onFinish
        return "success";
    }
 
    //完成任务投递回调
    public function onFinish($serv, $task_id, $data) {
        //task_worker进程将任务处理结果发送给worker进程
        echo "完成任务{$task_id}投递 处理结果：{$data}";
    }
 
    //关闭连接回调
    public function onClose($ws, $fd) {
        echo "{$fd}关闭了连接";
    }
}
 
$obj = new Ws();