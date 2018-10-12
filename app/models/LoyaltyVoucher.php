<?php

class LoyaltyVoucher extends \Basemodel {

	protected $collection = "loyaltyvouchers";

	protected $dates = ['expiry_date'];

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
    
    }
	
}