<?php

/** 
 * ModelName : Device.
 * Maintains a list of functions used for Device.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Device extends  \Basemodel {

	protected $collection = "devices";

	public static function maxId(){
        
        $model = "Device";
        
        $identitycounter =  Identitycounter::where('model', $model)->where('field', '_id')->first();

        if(empty($identitycounter)){
            return $model::max('_id');
        }

        $identitycounter_count =  $identitycounter->count;
        
        $update = Identitycounter::where('model', $model)->where('field', '_id')->where('count', $identitycounter_count)->increment('count');

        if($update){
            Log::info("returning::".strval($identitycounter_count));
            return $identitycounter_count;
        }
        Log::info("reiterating::".strval($identitycounter_count));
        return  $model::maxId();
    
    }
	
}