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
		Log::info("In model");

		DB::connection('mongodb2')->enableQueryLog();

		$finder = Finder::where('_id', $finder_id)->where('flags.gmv1','$exists',true)->get(['flags.gmv1']);
		Log::info("dfssfd   ::  ", [$finder[0]['flags']['gmv1']]);
		
		if(isset($finder) && $finder[0]['flags']['gmv1'] == true){
			Log::info("if");
			return $query->where($field_name, intval($field_value))->where('hidden', false)->orderBy('order', 'asc')
					->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->get();
		}else{
			Log::info("else");
			return $query->where($field_name, intval($field_value))->where('hidden', false)->orderBy('order', 'asc')
					->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->get();		
		}
	}
	
}
