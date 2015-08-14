<?PHP namespace App\Services;

use Cache, Response;

Class Cacheapi {

    public function flushTag($tag = false){

        if($tag){
            Cache::tags($tag)->flush();
            $responce = array('status'=>200);
        }else{
            $responce = array('status'=>400,'message'=>'error');
        }
        
        return Response::json($responce);                                       
    }

    public function flushTagKey($tag = false,$key = false){

        if($tag && $key){
            Cache::tags($tag)->forget($key);
            $responce = array('status'=>200);
        }else{
            $responce = array('status'=>400,'message'=>'error');
        }
        
        return Response::json($responce);                                       
    }

    public function flushAll(){
        
        Cache::flush();

        $responce = array('status'=>200);

        return Response::json($responce);                                       
    }


}                                       