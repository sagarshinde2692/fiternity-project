<?php

class Order extends \Basemodel {

	protected $collection = "orders";

	protected $dates = array('preferred_starting_date','start_date');

	
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}
	
	public function setCityIdAttribute($value){
		
		$this->attributes['city_id'] = intval($value);
	}

	public function setCustomerIdAttribute($value){
		
		$this->attributes['customer_id'] = intval($value);
	}

	public function setServiceIdAttribute($value){
		
		$this->attributes['service_id'] = intval($value);
	}

	public function setFinderIdAttribute($value){
		
		$this->attributes['finder_id'] = intval($value);
	}

	public function setCustomerSourceAttribute($value){
		
		$this->attributes['customer_source'] = strtolower($value);
	}

	public function setAmountAttribute($value){
		$this->attributes['amount'] = intval($value);
	}

	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function serviceoffer(){
		return $this->belongsTo('Serviceoffer');
	}
	
}