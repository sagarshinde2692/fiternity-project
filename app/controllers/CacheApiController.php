<?php
use App\Services\Cacheapi as Cacheapi;
class CacheApiController extends BaseController {

	public function __construct(Cacheapi $cacheapi) {
         parent::__construct();	
         $this->cacheapi = $cacheapi;
    }


	public function flushTag($tag = false){

		if($tag){
            Cache::tags($tag)->flush();
            $this->cacheapi->flushCacheFromAllInstances($tag);
			$responce = array('status'=>200);
		}else{
			$responce = array('status'=>400,'message'=>'error');
		}
		
		return Response::json($responce);										
	}

	public function flushTagKey($tag = false,$key = false){

		if($tag && $key){
            Cache::tags($tag)->forget($key);
            $this->cacheapi->flushCacheFromAllInstances($tag, $key);
			$responce = array('status'=>200);
		}else{
			$responce = array('status'=>400,'message'=>'error');
		}
		
		return Response::json($responce);										
	}

	public function flushAll(){
		
        Cache::flush();
        $this->cacheapi->flushCacheFromAllInstances();

		$responce = array('status'=>200);

		return Response::json($responce);										
	}


}																																																																																																																																																																																																																																																																										