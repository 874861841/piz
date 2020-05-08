<?php
/**
 * 通知任务类
 */
namespace app\task;

class rushOrderMatching{
    /**
     * 异步匹配订单
     * @param $rushId     抢购的id
     * @param $userId     用户的id
     *
     * @return bool
     */
    public function Matching($rushId,$userId,$buy_quantity,$buy_quantity_true,$host){
		
		\Piz\Db\Mysqli::ping(); //用于解决mysql gone away
    	$users = new \app\models\api\users();
		$activity_rush = new \app\models\api\activity_rush();
		$robots_order = new \app\models\api\robots_order();
		$activity_rush_order = new \app\models\api\activity_rush_order();
		$messages = new \app\models\api\messages();
		$host = explode(':', $host)['0'];
		$this->redis = \Piz\Redis::get_instance();
		$this->redis->lpush('rushtest'.$rushId,json_encode([$rushId,$userId,$buy_quantity,$buy_quantity_true]));
		
		$userArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$userId)->queryOne(TRUE);
		$rushArr = $activity_rush->makeSqlSelect("*")->makeSqlWhere('id',$rushId)->queryOne(TRUE);
	
		//对于信用值不够的，不允许匹配,扣减已经抢购的数量
//		$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rushId;
//		if($userArr['weight']<10){
//			$this->redis->decrby($ciosActivityRushLenghtKey,$buy_quantity_true);
//			return;
//		}

		//对于被冻结的账户，不进行匹配，扣减已经抢购的数量，扣减所需的机器人数量
		$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rushId;
		$ciosActivityRushQuantityCountKey = 'cios:activity:rushQuantityCount:' . $rushId;  //抢购总的台数
		if($userArr['status'] == 0){
			$this->redis->decrby($ciosActivityRushLenghtKey,$buy_quantity_true);		
			$this->redis->decrby($ciosActivityRushQuantityCountKey,$buy_quantity);
			return;
		}
		
		$sellOrders = $robots_order
		->makeSqlSelect("robots_order.*")
		->makeSqlJoin('robots_order_robot','robots_order_robot.order_id = robots_order.id','left join')
		->makeSqlJoin('robots','robots.id = robots_order_robot.robot_id','left join')
		->makeSqlWhere(['robots_order.created_at <'=>$rushArr['start_time'],'status'=>0,'buyer_id'=>0,'robots_order.deleted_at'=>'is null'])
		->makeSqlOrderBy(['mine_num'=>'ASC','is_special'=>'DESC','robots_order.created_at'=>'ASC'])
		->query(TRUE);
		
		if(empty($sellOrders)) return;
		
		$msgUserArr = [];
//		$j = 0;
		for ($i=0; $i < $buy_quantity_true; $i++) { 
			foreach ($sellOrders as $key => $value) {
				//不能卖给自己
				if($value['seller_id'] != $userArr['id']){
					$affected_rows = $robots_order->update(['id'=>$value['id'],'status' => 0],['buyer_id'=>$userArr['id'],'status' => 1,'updated_at'=>date('Y-m-d H:i:s',time())]);
					if($affected_rows == 1){
						$msgUserArr[$value['seller_id']] = $userArr['id'];
//						$j++;
						$activity_rush_order->insert(['rush_id'=>$rushArr['id'],'order_id'=>$value['id'],'created_at'=>date('Y-m-d H:i:s',time()),'updated_at'=>date('Y-m-d H:i:s',time())]);
						$messages->insert(['type'=>4,'user_id'=>$userId,'title'=>'首页头部消息','msg'=>'恭喜'.$userArr['username'].'！ 成功抢到机器人。','created_at'=>date('Y-m-d H:i:s',time()),'updated_at'=>date('Y-m-d H:i:s',time())]);
						break;
					}
				}
			}
		}
		//实际匹配到的数量如果小于强到的实际数量，就是有部分没匹配成功，进行扣减已经抢购的数量
//		if($j<$buy_quantity_true){
//			$quantity = $buy_quantity_true - $j;
//			$this->redis->decrby($ciosActivityRushLenghtKey,$quantity);
//		}
		
		//发短信给买家和卖家发短信
		if(!empty($msgUserArr)){
			foreach ($msgUserArr as $key => $value) {
				$sellerArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$key)->queryOne(TRUE);
				$buyerArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$value)->queryOne(TRUE);
				$username = $sellerArr['username'];
				$name = $buyerArr['nickname'];
				$sellerUrl = "http://$host/api/v1/sms/cash/sendRushMsg?username=$username&type=4&name=$name";
				$this->curl_get(FALSE,$sellerUrl,'');
//				$this->redis->lpush('rushsellercurl',$sellerUrl);
				$username = $buyerArr['username'];
				$buyUrl = "http://$host/api/v1/sms/cash/sendRushMsg?username=$username&type=5";
				$this->curl_get(FALSE,$buyUrl,'');
//				$this->redis->lpush('rushbuyercurl',$buyUrl);
			}
		}
			
    }


	public function curl_get($type, $url, $cookie) {//type与url为必传、若无cookie则传空字符串

	 if (empty($url)) {
	     return false;
	 }
	
	 $ch = curl_init();//初始化curl
	 curl_setopt($ch, CURLOPT_URL,$url);//抓取指定网页
	 curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
	 if($type){  //判断请求协议http或https
	 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
	 }
	//	 curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
	 if(!empty($cookie))curl_setopt($ch,CURLOPT_COOKIE,$cookie);  //设置cookie
	 $data = curl_exec($ch);//运行curl
	 curl_close($ch);
	 return $data;
	}
}