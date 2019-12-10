<?php

class NewCampaign extends \Basemodel {

	protected $collection = "campaignconfig";

	public static $withoutAppends = false;

	public static $setAppends = [];

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}

	// protected $dates = array('schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time');

    // protected $appends = array('fitapi_customoffer_order');
    
    public static $rules = array(
		'title' => 'required',
		'start_date' => 'required',
		'end_date' => 'required',
		// 'customer_email' => 'required|email',
		// 'customer_phone' => 'required|numeric',
	);
    
    public static function maxId(){
        
        $model = "NewCampaign";
        
        $identitycounter =  Identitycounter::where('model', $model)->where('field', '_id')->first();

        if(empty($identitycounter)){
            Log::info("empty identitycounter");
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