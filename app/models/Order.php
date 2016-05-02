<?php

class Order extends \Basemodel {

	protected $collection = "orders";

	protected $appends = array('customer_took_trial_before');

	protected $dates = array('preferred_starting_date','start_date','start_date_starttime');

	
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

	public function getCustomerTookTrialBeforeAttribute(){

		$tooktrialbefore = "no";
//		print_r($this); exit;
//		echo $this->finder_id; exit;
		if(isset($this->customer_email) && $this->customer_email != "" && isset($this->finder_id) && $this->finder_id != 0){

			$booktrialcount = Booktrial::where('customer_email',$this->customer_email)
				->where('finder_id',intval($this->finder_id))
				->count();
			$tooktrialbefore = ($booktrialcount > 0) ? "yes" : "no";

		}

		return $tooktrialbefore;
	}




	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function serviceoffer(){
		return $this->belongsTo('Serviceoffer');
	}
	
}