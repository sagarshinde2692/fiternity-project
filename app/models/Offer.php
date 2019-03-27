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
		Log::info("filed_name  ", [$field_name]);
		Log::info("field_value  ", [$field_value]);
		Log::info("finder  ", [$finder_id]);
		Log::info("In model");

		DB::connection('mongodb2')->enableQueryLog();
		
		$gmv1Flag = false;

		if(!empty($finder_id['flags'])){

			Log::info("full finder");
			
			if(!empty($finder_id['flags']['gmv1'])){
				$gmv1Flag = $finder_id['flags']['gmv1'];
			}
			
		}else{

			if(!empty($GLOBALS['gmvFlag'][$finder_id])){
				Log::info("global");
				$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id];
			}else{
				Log::info("else condition");
				$finder = Finder::where('_id', $finder_id)->where('flags.gmv1','$exists',true)->get(['flags.gmv1']);
				if(count($finder) > 0){
					$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id] = $finder[0]['flags']['gmv1'];
				}else{
					$gmv1Flag = $GLOBALS['gmvFlag'][$finder_id] = false;
				}
				
			}
		}
		
		if($gmv1Flag == true){
			Log::info("if");
			return $query->where($field_name, intval($field_value))->where('hidden', false)->orderBy('_id', 'desc')
					->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
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
