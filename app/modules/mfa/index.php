<?php
namespace app\modules\mfa;
use app\modules\mfa\lib\conf;
class index extends \Piz\Controller
{
    public function tellName(){
        /*$user = new \app\model\User();
        $ret = $user->get_by_username ('ADMIN');
        var_dump ($ret);*/
        echo conf::NAME;
        echo  date('Y-m-d h:I:s'),'<br/>';
        echo __CLASS__;
    }
}