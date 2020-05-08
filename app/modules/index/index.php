<?php
namespace app\modules\index;
class index extends \Piz\Controller
{
    public function init(){
        /*$user = new \app\model\User();
        $ret = $user->get_by_username ('ADMIN');
        var_dump ($ret);*/
        var_dump('weiyu');
        echo  date('Y-m-d h:I:s'),'<br/>';
        echo __CLASS__;
    }
}