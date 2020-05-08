<?php

namespace Piz;


class Request
{
    /**
     * 对象实例
     * @var object
     */
    private static $instance;
    /**
     * 为了防止在请求的过程发生一些不愉快事情，全给它private
     * @var array
     */
    private $server;
    private $header;
    private $request;
    private $post;
    private $get;
    private $cookie;
    private $files;
    private $tmpfiles;
    private $rawContent;
    private $getData;

    private function __construct (){}

    public static function get_instance(){
        if(is_null (self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    //先拿到$request，然后挨个给它变身
    public function set($request){
        $this->server       = $request->server;
        $this->header       = $request->header;
        $this->tmpfiles     = $request->tmpfiles;
//      $this->request      = $request->request;
        $this->cookie       = $request->cookie;
        $this->get          = $request->get;
        $this->files        = $request->files;
        $this->post         = $request->post;
        $this->rawContent   = $request->rawContent();
        $this->getData      = $request->getData();
    }
    /*
    // 以上变身方法也可以用魔术方法，我写了，可就是不想用它。
    public function __set($name,$value){
        $this->$name = $value;
    }
    */
    //变身后它就不是废物了，那就得让小伙伴们能使用它，这里使用了这么一个魔术方法。
    public function __get($name){
        return $this->$name;
    }
}