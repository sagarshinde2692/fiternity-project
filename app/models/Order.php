<?php

class Order extends \Basemodel {

	protected $collection = "orders";

	public static $withoutAppends = false;

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return [];
		}
		return parent::getArrayableAppends();
	}
	
	protected $dates = array('preferred_starting_date','start_date','start_date_starttime','end_date','preferred_payment_date','success_date','pg_date','preferred_starting_change_date','dietplan_start_date','followup_date', 'order_confirmation_customer','auto_followup_date','requested_preferred_starting_date');

	protected $hidden = array('verify_payment_hash');
	
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

	public function setAmountFinderAttribute($value){
		$this->attributes['amount_finder'] = intval($value);
	}

	public function finder(){
		return $this->belongsTo('Finder');
	}

    public function service(){
		return $this->belongsTo('Service');
	}

	public function serviceoffer(){
		return $this->belongsTo('Serviceoffer');
	}
	public function trainerslotbookings(){
		Log::info("yo");
		return $this->hasMany('TrainerSlotBooking','order_id');
	}
    
    public function ticket(){
		return $this->belongsTo('Ticket');
    }
    
    public function customerreward(){
		return $this->belongsTo('Myreward', 'customer_reward_id');
    }
    
    
	
}