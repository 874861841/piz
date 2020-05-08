<?php
/**
 * Task类
 */
namespace Piz;

final class Upload
{
    /**
     * 实例
     * @var object
     */
    private static $instance ;

    private $request ;

    private function __construct (){}

    final public static function get_instance(){
        if( is_null(self::$instance) ) {
            self::$instance = new self();

        }
        return self::$instance;
    }

    final public function set_request($request){
		$this->request = $request;
        return self::$instance;
    }
	
	final public function do_upload(){
		if ($_FILES = $this->request->files){
			foreach ($_FILES as $key => $file) {
				//判断上传的文件是否出错,是的话，返回错误
			    if($file["error"])
			    {
					throw new \Exception($file["error"], 400);  //抛出异常
			    }
			    else
			    {
			    	if($file["size"]>10240000) throw new \Exception('文件过大,请勿超10240000B', 400);  //抛出异常
			        //判断上传文件类型为png或jpg且大小不超过1024000B
			        if(($file["type"]=="image/png"||$file["type"]=="image/jpeg"||$file["type"]=="image/jpg"||$file["type"]=="image/gif")&&$file["size"]<10240000)
			        {
			            //防止文件名重复
			            $fileNew = md5($file["tmp_name"]).'.'.pathinfo($file["name"],PATHINFO_EXTENSION); //md5转换的 tmp_name 连接. 文件后缀
			            $path = "uploads/images/".date('Y-m-d')."/".time().$fileNew;
			            $filename = STATIC_PATH.$path;
			            //转码，把utf-8转成gb2312,返回转换后的字符串， 或者在失败时返回 FALSE。
			            //$filename =iconv("UTF-8","gb2312",$filename);
			            //检查文件或目录是否存在
			            if(file_exists($filename))
			            {
							throw new \Exception('该文件已存在', 400);  //抛出异常
			            }
			            else
			            {
			            	//判断文件夹是否存在，不存在就创建文件夹
			            	if(!is_dir($imagesDir = STATIC_PATH.'uploads/images/')){
			            		mkdir($imagesDir,0777);
			            	}
			                //保存文件,   move_uploaded_file 将上传的文件移动到新位置
			                if (!@copy($file['tmp_name'], $filename)){
			                    $dir = STATIC_PATH.'uploads/images/'.date('Y-m-d');
			                    if(!is_dir($dir)){
			                        mkdir($dir,0777);
			                    }
			                    if(move_uploaded_file($file['tmp_name'],$filename)){
			                    	$filenameArr[$key] = $path;
			                    }else{
			                        throw new \Exception('上传失败', 400);  //抛出异常
			                    }
			                }else {
			                	$filenameArr[$key] = $path;
			                }
			
			
			            }
			        }else
			        {
						throw new \Exception('文件类型不对', 400);  //抛出异常
			        }
			    }
			}
		    return $filenameArr;
		}
    }

    public function __get($name){
        return $this->$name;
    }
}