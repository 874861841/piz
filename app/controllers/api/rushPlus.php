<?php
namespace app\controllers\api;
use app\controllers\api\lib\conf;
//use Piz;
class rushPlus extends \app\core\api_controller
{
	
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
	
    public function rush(){
    	try{
    		//用来测试任务
 		$rushOrderMatching = new \app\task\rushOrderMatching();
 		$rushOrderMatching->Matching(93,3,1,1,'main.cios.pro:9500');
		return;	
			var_dump($result);return;
//  		$host = explode(':', $this->request->header['host'])['0'];
//			var_dump($host);
//  		return;
//  		$username = 15625037905;
//			$name = 15625037905;
//  		$url = "http://114.55.172.250/api/v1/sms/cash/sendRushMsg?username=$username&type=4&name=$name";
//			echo $url;
//			$this->curl_get(FALSE,$url,'');return;
			
//			$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//$str = curl_exec($ch);return;
    	$rushId = 81;
		$userId = 2416;
		$buy_quantity = 3;
		$buy_quantity_true = 1;
		$host = '114.55.172.250:9500';
    	\Piz\Db\Mysqli::ping(); //用于解决mysql gone away
    	$users = new \app\models\api\users();
		$activity_rush = new \app\models\api\activity_rush();
		$robots_order = new \app\models\api\robots_order();
		$activity_rush_order = new \app\models\api\activity_rush_order();
		$host = explode(':', $host)['0'];
		$this->redis = \Piz\Redis::get_instance();
		$this->redis->lpush('rushtest'.$rushId,json_encode([$rushId,$userId,$buy_quantity,$buy_quantity_true]));
		
		$userArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$userId)->queryOne(TRUE);
		$rushArr = $activity_rush->makeSqlSelect("*")->makeSqlWhere('id',$rushId)->queryOne(TRUE);
	
		//对于信用值不够的，不允许匹配,扣减已经抢购的数量
		$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rushId;
		if($userArr['weight']<10){
			$this->redis->decrby($ciosActivityRushLenghtKey,$buy_quantity_true);
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
		$j = 0;
		for ($i=0; $i < $buy_quantity_true; $i++) { 
			foreach ($sellOrders as $key => $value) {
				//不能卖给自己
				if($value['seller_id'] != $userArr['id']){
					$affected_rows = $robots_order->update(['id'=>$value['id'],'status' => 0],['buyer_id'=>$userArr['id'],'status' => 1,'updated_at'=>date('Y-m-d H:i:s',time())]);
					if($affected_rows == 1){
						$msgUserArr[$value['seller_id']] = $userArr['id'];
						$j++;
						$activity_rush_order->insert(['rush_id'=>$rushArr['id'],'order_id'=>$value['id'],'created_at'=>date('Y-m-d H:i:s',time()),'updated_at'=>date('Y-m-d H:i:s',time())]);
						break;
					}
				}
			}
		}
		//实际匹配到的数量如果小于强到的实际数量，就是有部分没匹配成功，进行扣减已经抢购的数量
		if($j<$buy_quantity_true){
			$quantity = $buy_quantity_true - $j;
			$this->redis->decrby($ciosActivityRushLenghtKey,$quantity);
		}
		
		//发短信给买家和卖家发短信
		if(!empty($msgUserArr)){
			foreach ($msgUserArr as $key => $value) {
				$sellerArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$key)->queryOne(TRUE);
				$buyerArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$value)->queryOne(TRUE);
				$username = $sellerArr['username'];
				$name = $buyerArr['nickname'];
				$sellerUrl = "http://$host/api/v1/sms/cash/sendRushMsg?username=$username&type=4&name=$name";
				$this->curl_get(FALSE,$sellerUrl,'');
				$this->redis->lpush('rushsellercurl',$sellerUrl);
				$username = $buyerArr['username'];
				$buyUrl = "http://$host/api/v1/sms/cash/sendRushMsg?username=$username&type=5";
				$this->curl_get(FALSE,$buyUrl,'');
				$this->redis->lpush('rushbuyercurl',$buyUrl);
			}
		}
				
		var_dump($j);return;
    		$this->queue = \app\controllers\api\lib\queue::get_instance()->set_server ($this->server); 
					$this->queue->rushOrderMatching(1,100);					
					return $this->returnJson(1,['join_num'=>1],'success',1);
    		//判断参数是否正确
    		$judgeParamResult = $this->judgeParam(['rush_id'],'POST');
    		if(FALSE === $judgeParamResult['state']) return $this->returnJson(0,[],$judgeParamResult['message'],0);

    		$tokenKey = 'cios:token:'.$this->request->header['authorization'];
    		$tokenValue = $this->redis->get($tokenKey);
    		if(empty($tokenValue)) return $this->returnJson(0,[],'请登录',0);
    		
			$userArr = json_decode($tokenValue,TRUE);
			$rush_id = $this->request->post['rush_id'];
			
			$ciosActivityRushKey = 'cios:activity:rush:' . $rush_id;
        	$ciosActivityRushUserKey = "cios:activity:rushUser:$rush_id:" . $userArr['id'];
			$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rush_id;
			
			$activityRushStr = $this->redis->get($ciosActivityRushKey);
        	if( !$activityRushStr ) return $this->returnJson(0,[],'活动已删除或者不存在',0);
			
			$activityRushArr = json_decode($activityRushStr,TRUE);
        	if( strtotime($activityRushArr['start_time']) > time()) return $this->returnJson(0,[],'活动未开始',0);
       		if( strtotime($activityRushArr['end_time']) < time()) return $this->returnJson(0,[],'活动已结束',0);
				
			if(TRUE === $this->redis->set($ciosActivityRushUserKey,1,array('nx', 'ex' => 86400))){
				$ciosActivityRushLenght = $this->redis->incr($ciosActivityRushLenghtKey);
				if($ciosActivityRushLenght <= $activityRushArr['robots_num']){
					//加入到队列
	            	$this->redis->lpush('rush.' . $rush_id . '.users',$userArr['id']);
					$this->queue = \app\controllers\api\lib\queue::get_instance()->set_server ($this->server); 
					$this->queue->rushOrderMatching($rush_id,$userArr['id']);					
					return $this->returnJson(1,['join_num'=>$ciosActivityRushLenght],'success',1);
				}else {
					return $this->returnJson(0,[],'很遗憾，您未抢到',0);
				}
				
        	}else{
            	return $this->returnJson(0,[],'已经入队列了',0);
        	}
				

			
				
						
    	}catch (\Throwable $e) {
    		echo $e->__toString();
		}
    }
	
	public function mysqlPing(){
		try {
        	\Piz\Db\Mysqli::ping();
        	//判断参数是否正确
    		// $judgeParamResult = $this->judgeParam(['userId'],'GET');
    		// if(FALSE === $judgeParamResult['state']) return $this->returnJson(0,['msg'=>$judgeParamResult['message'],'code'=>0],$judgeParamResult['message'],0);
        	$userId = $this->request->get['userId'];
        	$mobile = $this->request->get['mobile'];
			$users = new \app\models\api\users();
			if(empty($userId)){
				$userArr = $users->makeSqlSelect("*")->makeSqlWhere('mobile',$mobile)->queryOne(TRUE);
			}else{
				$userArr = $users->makeSqlSelect("*")->makeSqlWhere('id',$userId)->queryOne(TRUE);
			}
			$userArr = $users->makeSqlSelect("*")->makeSqlWhere('mobile',$mobile)->queryOne(TRUE);
			return $this->returnJson(0,$userArr,'操作成功',0);
   		} catch (mysqli_sql_exception $e) { 
      		throw $e; 
   		} 
	}
	
}