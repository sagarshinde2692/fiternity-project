<?php

class Booktrial extends \Basemodel {


	protected $collection = "booktrials";

	public static $withoutAppends = false;

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return [];
		}
		return parent::getArrayableAppends();
	}

	protected $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');

	public static $unset_dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

	public function setCodeAttribute($value){

		$this->attributes['code'] = (string) $value;
	}

	
	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function city(){
		return $this->belongsTo('City','city_id');
	}

	public function category(){
		return $this->belongsTo('Findercategory','finder_category_id');
	}
	public function invite(){
		return $this->hasMany('Invite','referrer_booktrial_id');
	}

	public function service(){
		return $this->belongsTo('Service','service_id');
	}

	public function findercategory(){
		return $this->hasOne('Findercategory', 'findercategory_id');
    }
    
    public static function maxId(){
        
        $identitycounter =  Identitycounter::where('model', 'Booktrial')->where('field', '_id')->first();
        $identitycounter_count =  $identitycounter->count;
        
        $update = Identitycounter::where('model', 'Booktrial')->where('field', '_id')->where('count', $identitycounter_count)->increment('count');

        if($update){
            Log::info("returning::".strval($identitycounter_count));
            return $identitycounter_count;
        }
        Log::info("reiterating::".strval($identitycounter_count));
        return  Booktrial::maxId();
    }

}
