<?php

class Offer extends \Basemodel {

    protected $connection = 'mongodb2';

    protected $collection = "offers";

    protected $dates = array('start_date','end_date');


	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}
	
	public function setCityIdAttribute($value){
		$this->attributes['city_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}

	public function city(){
		return $this->belongsTo('City');
	}

    public function ratecard(){
        return $this->belongsTo('Ratecard');
    }

    public function finder(){
        return $this->belongsTo('Finder', 'vendor_id');
    }

    public function service(){
        return $this->belongsTo('Service', 'vendorservice_id');
    }

    public function scopeActive ($query){

		return 	$query->where('hidden', false)->where('start_date', '<=', new \DateTime( date("d-m-Y 00:00:00", time()) ))->where('end_date', '>=', new \DateTime( date("d-m-Y 00:00:00", time()) ));
	}

	public function scopeGetActiveV1($query, $field_name, $field_value, $finder_id){

		DB::connection('mongodb2')->enableQueryLog();
		
		$gmv1Flag = false;

		if(is_object($finder_id)){
			//Log::info("full finder");
			if(!empty($finder_id['flags']['gmv1'])){
				//Log::info("full finder gmv");
				$gmv1Flag = $finder_id['flags']['gmv1'];
			}
		}else{

			if(!empty($GLOBALS['gmvFlag'][$finder_id])){
				Log::info("global");
				$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id];
			}else{
				Log::info("else condition");
				$finder = Finder::where('_id', $finder_id)->where('flags.gmv1','exists',true)->first(['flags.gmv1']);
				// Log::info("f  :::  ", [$finder]);
				if(count($finder) > 0){
					$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id] = $finder['flags']['gmv1'];
				}else{
					$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id] = false;
				}
				
			}
		}
		
		if($gmv1Flag == true){
			Log::info("if");
			return $query->where($field_name, intval($field_value))->where('hidden', false)->orderBy('_id', 'desc')
                    ->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
                    ->where(function($query){$query->orWhere('created_at', '>', new DateTime( date("d-m-Y 00:00:00", strtotime('2019-07-31')) ))->orWhere('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ));})
                    ->get();
		}else{
			Log::info("else");
			return $query->where($field_name, intval($field_value))->where('hidden', false)->orderBy('_id', 'desc')
					->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->get();		
		}
	}
	
}
