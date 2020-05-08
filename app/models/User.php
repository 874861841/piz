<?php
namespace app\model;
class User extends \Piz\Model
{
    public $table_name = 'USER';

    public function get_by_username($username){
        return $this->get_one ("`USERNAME`='{$username}'");
    }

}