<?PHP namespace App\Services;

use Cache, Response;

Class Cacheapi {

    public function flushTag($tag = false){

        if($tag){
            Cache::tags($tag)->flush();

            $this->flushCacheFromAllInstances($tag);
            
            $responce = array('status'=>200);
        }else{
            $responce = array('status'=>400,'message'=>'error');
        }
        
        return Response::json($responce);                                       
    }

    public function flushTagKey($tag = false,$key = false){

        if($tag && $key){
            Cache::tags($tag)->forget($key);
            
            $this->flushCacheFromAllInstances($tag, $key);
            
            $responce = array('status'=>200);
        }else{
            $responce = array('status'=>400,'message'=>'error');
        }
        
        return Response::json($responce);                                       
    }

    public function flushAll(){
        
        Cache::flush();

        $this->flushCacheFromAllInstances();

        $responce = array('status'=>200);

        return Response::json($responce);                                       
    }

    public function flushCacheFromAllInstances($tag=null, $key=null){

        if(!\Config::get('app.debug')){

            $api_instance_urls = ['r1.fitternity.com/', 'r2.fitternity.com/', 'r5.fitternity.com/'];
            // $api_instance_urls = ['apistage.fitn.in/', 'apistage.fitn.in/', 'apistage.fitn.in/'];
            // $api_instance_urls = ['apistage.fitn.in/'];
    
            if($tag && $key){

                $route = 'flushtagkey/'.$tag.'/'.$key;
    
            }else if($tag && !$key){
                
                $route = 'flushtag/'.$tag;
                
            }else {
                
                $route = 'cachedrop';
    
            }
            
            foreach($api_instance_urls as $url){
                \Log::info(curl_call_get($url.$route));
            }
        }
        
    }


}                                       