<?php
/**
 * Websocket Controller
 */
namespace Piz;
class WsController
{
    /**
     * @var \Piz\Router
     */
    protected $router;
    /**
     * @var swoole_server
     */
    protected $server;
    /**
     * @var swoole_server->frame
     */
    protected $frame;
    /**
     * @var swoole_server->task
     */
    protected $task;

    public function __set($name,$object){
        $this->$name = $object;
    }

    public function __get($name){
        return $this->$name;
    }

}