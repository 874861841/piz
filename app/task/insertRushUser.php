<?php
/**
 * 通知任务类
 */
namespace app\task;

class insertRushUser{
    /**
     * 异步匹配订单
     * @param $rushId     抢购的id
     * @param $userId     用户的id
     *
     * @return bool
     */
    public function insert($rushId,$userId,$buy_quantity){
    	\Piz\Db\Mysqli::ping(); //用于解决mysql gone away
    	$this->redis = \Piz\Redis::get_instance();
    	$activity_rush_user = new \app\models\api\activity_rush_user();
		
		$ciosActivityRushQuantityCountKey = 'cios:activity:rushQuantityCount:' . $rushId;  //抢购总的台数
		$this->redis->incrby($ciosActivityRushQuantityCountKey,$buy_quantity);
		
		$activity_rush_user->insert(['rush_id'=>$rushId,'user_id'=>$userId,'buy_quantity'=>$buy_quantity,'created_at'=>date('Y-m-d H:i:s',time()),'updated_at'=>date('Y-m-d H:i:s',time())]);
    }
}