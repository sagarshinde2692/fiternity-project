<?php

class Serviceoffer extends \Basemodel {

	public static $rules = [
	'ratecard_id' => 'required|numeric'
	];

	protected $dates = array('start_date','end_date');

	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}

	public function setPriceAttribute($value){
		$this->attributes['price'] = intval($value);
	}

	public function setLimitAttribute($value){
		$this->attributes['limit'] = intval($value);
	}

	public function setFinderIdAttribute($value){
		$this->attributes['finder_id'] = intval($value);
	}
	
	public function setServiceIdAttribute($value){
		$this->attributes['service_id'] = intval($value);
	}

	public function setRatecardIdAttribute($value){
		$this->attributes['ratecard_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}


	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function service(){
		return $this->belongsTo('Service','service_id');
	}

	public function ratecard(){
		return $this->belongsTo('Ratecard','ratecard_id');
	}


}