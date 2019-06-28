<?php

class RazorpayPlan extends  \Basemodel {
	
	protected $collection = "razorpayplans";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	public static function maxId(){
        
        $model = 'RazorpayPlan';
        
        $identitycounter =  Identitycounter::where('model', $model)->where('field', '_id')->first();

        if(empty($identitycounter)){
            return $model::max('plan_id');
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