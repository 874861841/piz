<?php
namespace app\controllers\api;
use app\controllers\api\lib\conf;
//use Piz;
class rush extends \app\core\api_controller
{
    public function rush1(){
    	try{
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
			
			$activityRushStr = $this->redis->get($ciosActivityRushKey);
        	if( !$activityRushStr ) return $this->returnJson(0,[],'活动已删除或者不存在',0);			
			$activityRushArr = json_decode($activityRushStr,TRUE);
			
        	if( strtotime($activityRushArr['start_time']) > time()) return $this->returnJson(0,[],'活动未开始',0);
       		if( strtotime($activityRushArr['end_time']) < time()) return $this->returnJson(0,[],'活动已结束',0);
			
			if(TRUE === $this->redis->setnx($ciosActivityRushUserKey,1)){
            	$this->redis->lpush('rush.' . $rush_id . '.users',$userArr['id']);
				return $this->returnJson(1,['join_num'=>1],'success',1);
        	}else{
            	return $this->returnJson(0,[],'已经入队列了',0);
        	}
						
    	}catch (\Throwable $e) {
    		echo $e->__toString();
		}
    }
	
	/*
	 * 抢购点击接口
	 * */
	public function rush(){
    	try{
    		//判断参数是否正确
    		$judgeParamResult = $this->judgeParam(['rush_id','buy_quantity'],'POST');
    		if(FALSE === $judgeParamResult['state']) return $this->returnJson(0,['msg'=>$judgeParamResult['message'],'code'=>0],$judgeParamResult['message'],0);

    		$tokenKey = 'cios:token:'.$this->request->header['authorization'];
    		$tokenValue = $this->redis->get($tokenKey);
    		if(empty($tokenValue)) return $this->returnJson(0,['msg'=>'请登录','code'=>0],'请登录',0);
    		
			$userArr = json_decode($tokenValue,TRUE);
			$rush_id = $this->request->post['rush_id'];
			$buy_quantity = $this->request->post['buy_quantity'];
			$buy_quantity = intval($buy_quantity);			
			if($buy_quantity <= 0) return $this->returnJson(1,['msg'=>'很遗憾，您未抢到','code'=>0],'很遗憾，您未抢到',0);
			
			$ciosActivityRushKey = 'cios:activity:rush:' . $rush_id;
        	$ciosActivityRushUserKey = "cios:activity:rushUser:$rush_id:" . $userArr['id'];
			$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rush_id;  //抢购限制的台数			
			
			$activityRushStr = $this->redis->get($ciosActivityRushKey);
        	if( !$activityRushStr ) return $this->returnJson(1,['msg'=>'活动已删除或者不存在','code'=>0],'活动已删除或者不存在',1);
			
			$activityRushArr = json_decode($activityRushStr,TRUE);
        	if( strtotime($activityRushArr['start_time']) > time()) return $this->returnJson(1,['msg'=>'活动未开始','code'=>0],'活动未开始',1);
       		if( strtotime($activityRushArr['end_time']) < time()) return $this->returnJson(1,['msg'=>'活动已结束','code'=>0],'活动已结束',1);
			
			//判断个抢购的数量有无大于个人允许抢购的最大数量
			if($buy_quantity > $activityRushArr['robots_num_everyone']) return $this->returnJson(1,['msg'=>'超过允许请购的数量','code'=>0],'超过允许请购的数量',1);
			if(TRUE === $this->redis->set($ciosActivityRushUserKey,$buy_quantity,array('nx', 'ex' => 86400))){
				
				
//				$ciosActivityRushLenght = $this->redis->incrby($ciosActivityRushLenghtKey,$buy_quantity); //用户需抢购的总数
//				
//				if($ciosActivityRushLenght < ($activityRushArr['robots_num'] + $buy_quantity)){
//					//加入到队列
//	            	$this->redis->lpush('rush.' . $rush_id . '.users',$userArr['id']);
//					$this->queue = \app\controllers\api\lib\queue::get_instance()->set_server ($this->server); 
//					$this->queue->rushOrderMatching($rush_id,$userArr['id'],$buy_quantity);
//					return $this->returnJson(1,['join_num'=>$ciosActivityRushLenght + $activityRushArr['join_num']],'恭喜您已经抢到',1);
//				}else {
//					return $this->returnJson(0,[],'很遗憾，您未抢到',0);
//				}

				//异步处理，记录点击过的用户
				$this->queue = \app\controllers\api\lib\queue::get_instance()->set_server ($this->server);
				$this->queue->insertRushUser($rush_id,$userArr['id'],$buy_quantity);
				
				$ciosActivityRushLenght = $this->redis->get($ciosActivityRushLenghtKey);
				if($ciosActivityRushLenght < $activityRushArr['robots_num']){
					if(($ciosActivityRushLenght + $buy_quantity) < $activityRushArr['robots_num']){
						$buy_quantity_true = $buy_quantity;
						$this->redis->incrby($ciosActivityRushLenghtKey,$buy_quantity);
					}else {
						$buy_quantity_true = $activityRushArr['robots_num'] - $ciosActivityRushLenght;
						$this->redis->set($ciosActivityRushLenghtKey,$activityRushArr['robots_num']);
					}
					//加入到队列
	            	$this->redis->lpush('rush.' . $rush_id . '.users',$userArr['id']);
					//异步处理进行匹配 
					$this->queue->rushOrderMatching($rush_id,$userArr['id'],$buy_quantity,$buy_quantity_true,$this->request->header['host']);
					
//					$length = $this->redis->llen('rush.' . $rush_id . '.users',$userArr['id']);			
					return $this->returnJson(1,['msg'=>'抢购成功','code'=>1],'抢购成功',1);					
				}else {
					return $this->returnJson(1,['msg'=>'很抱歉，您未抢到机器人','code'=>0],'很抱歉，您未抢到机器人',0);
				}
				
				
        	}else{
        		return $this->returnJson(1,['msg'=>'很遗憾，您未抢到','code'=>0],'很遗憾，您未抢到',0);
//      		$users = $this->redis->lrange('rush.' . $rush_id . '.users', 0 ,-1);
//				if(in_array($userArr['id'],$users)){
//					return $this->returnJson(1,['join_num'=>$this->redis->get($ciosActivityRushLenghtKey) + $activityRushArr['join_num']],'success',1);
//				}else {
//					return $this->returnJson(0,['msg'=>'很遗憾，您未抢到'],'很遗憾，您未抢到',0);
//				}
        	}
						
    	}catch (\Throwable $e) {
    		echo $e->__toString();
		}
		
	}	
	
	/*
	 * 抢购进度
	 * */	
	public function evolve(){
    	try{
    		//判断参数是否正确
    		$judgeParamResult = $this->judgeParam(['rush_id'],'POST');
    		if(FALSE === $judgeParamResult['state']) return $this->returnJson(0,['msg'=>$judgeParamResult['message']],$judgeParamResult['message'],0);

    		$tokenKey = 'cios:token:'.$this->request->header['authorization'];
    		$tokenValue = $this->redis->get($tokenKey);
    		if(empty($tokenValue)) return $this->returnJson(0,['msg'=>'请登录'],'请登录',0);
    		
			$userArr = json_decode($tokenValue,TRUE);
			$rush_id = $this->request->post['rush_id'];
			
			$ciosActivityRushKey = 'cios:activity:rush:' . $rush_id;
			$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rush_id;  //抢购限制的台数
			$ciosActivityRushQuantityCountKey = 'cios:activity:rushQuantityCount:' . $rush_id;  //抢购总的台数
			
			$activityRushStr = $this->redis->get($ciosActivityRushKey);
        	if( !$activityRushStr ) return $this->returnJson(0,['msg'=>'活动已删除或者不存在'],'活动已删除或者不存在',0);			
			$activityRushArr = json_decode($activityRushStr,TRUE);
			
			$data['msg'] = 'success';			
			if(strtotime($activityRushArr['start_time']) < time()){
				$ciosActivityRushQuantityCount = $this->redis->get($ciosActivityRushQuantityCountKey)??0;
				$data['robots_rush_rate'] = bcdiv($ciosActivityRushQuantityCount,$activityRushArr['robots_num'],2);
				$data['robots_rush_need_quantity'] = intval($ciosActivityRushQuantityCount);
//				$ciosActivityRushQuantityCount = $this->redis->get($ciosActivityRushQuantityCountKey)??0;
//				$ciosActivityRushLenght = $this->redis->get($ciosActivityRushLenghtKey);
//				$data['robots_rush_rate'] = bcdiv($ciosActivityRushQuantityCount + $activityRushArr['join_num'],$activityRushArr['robots_num'] + $activityRushArr['join_num'],2);
//				//在实际挂单数小于界面的抢购数，则会出现抢完了还没到100% 的情况
//				if(($ciosActivityRushLenght >= $activityRushArr['robots_num']) && ($data['robots_rush_rate'] < 1)){
//					$data['robots_rush_rate'] = '1.00';
//				}
//				$data['robots_rush_need_quantity'] = intval(bcmul($activityRushArr['robots_num'],$data['robots_rush_rate']));
			}else {
				$data['robots_rush_need_quantity'] = 0;
				$data['robots_rush_rate'] = 0;
			}
			
			return $this->returnJson(1,$data,'success',1);
						
    	}catch (\Throwable $e) {
    		echo $e->__toString();
		}
    }
		
		
	public function getRush()
	{
		//判断参数是否正确
		$judgeParamResult = $this->judgeParam(['rush_id'],'POST');
		if(FALSE === $judgeParamResult['state']) return $this->returnJson(0,['msg'=>$judgeParamResult['message']],$judgeParamResult['message'],0);

		$tokenKey = 'cios:token:'.$this->request->header['authorization'];
		$tokenValue = $this->redis->get($tokenKey);
		if(empty($tokenValue)) return $this->returnJson(0,['msg'=>'请登录'],'请登录',0);
		
		$userArr = json_decode($tokenValue,TRUE);
		$rush_id = $this->request->post['rush_id'];
		
		$ciosActivityRushKey = 'cios:activity:rush:' . $rush_id;
		$ciosActivityRushLenghtKey = 'cios:activity:rushLenght:' . $rush_id;  //抢购限制的台数
		$ciosActivityRushQuantityCountKey = 'cios:activity:rushQuantityCount:' . $rush_id;  //抢购总的台数
		
		$activityRushStr = $this->redis->get($ciosActivityRushKey);
    	if( !$activityRushStr ) return $this->returnJson(0,['msg'=>'活动已删除或者不存在'],'活动已删除或者不存在',0);			
		$activityRushArr = json_decode($activityRushStr,TRUE);
		
		$activityRushArr['start_time'] = date('H:i:s',strtotime($activityRushArr['start_time']));
		$time = strtotime($activityRushArr['start_time']) - time();
        $activityRushArr['remaining_time_plus'] = $this->secToTime($time); //全时间倒计时
        if($time > 0 && $time < 60 * 60){
            $activityRushArr['remaining_time'] = $time;
        }elseif( strtotime($activityRushArr['start_time']) < time() && strtotime($activityRushArr['end_time']) > time() ){
			//前端用的倒计时
            $timeRange = strtotime($activityRushArr['end_time'])-time();           
            $activityRushArr['remaining_time'] = $timeRange;

            //判断是否已经点击
            $ciosActivityRushUserKey = "cios:activity:rushUser:$rush_id:" . $userArr['id'];
            if(empty($this->redis->get($ciosActivityRushUserKey))){
                $activityRushArr['status'] = 1;  // 抢购（未点击抢购按钮）
            }else{
                $activityRushArr['status'] = 2;  // 已抢购（已点击抢购按钮）
            }

            //判断是否已经抢光
            $ciosActivityRushLenght = $this->redis->get($ciosActivityRushLenghtKey);
            $ciosActivityRushLenght = is_null($ciosActivityRushLenght)?0:$ciosActivityRushLenght;
            if($ciosActivityRushLenght>=$activityRushArr['robots_num']){
                if($activityRushArr['status'] != 1){
                    $activityRushArr['status'] = 3;  //已抢光
                }
            }

        }elseif (strtotime($activityRushArr['end_time']) < time()) {
            //已经结束
            $activityRushArr['status'] = 4;  //已结束
            $activityRushArr['remaining_time'] = '';
        }else{
            $activityRushArr['remaining_time'] = '';
        }
		
		return $this->returnJson(1,$activityRushArr,'success',1);
	}
	
	private function secToTime($times){  
        $result = '00:00:00';  
        if ($times>0) {  
                $hour = floor($times/3600);  
                $minute = floor(($times-3600 * $hour)/60);  
                $second = floor((($times-3600 * $hour) - 60 * $minute) % 60); 
                $hour = $hour>=10?$hour:'0'.$hour;
                $minute = $minute>=10?$minute:'0'.$minute; 
                $second = $second>=10?$second:'0'.$second;
                $result = $hour.':'.$minute.':'.$second;
        }  
        return $result;  
	}
    
}