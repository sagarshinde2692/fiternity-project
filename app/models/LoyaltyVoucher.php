<?php

class LoyaltyVoucher extends \Basemodel {

	protected $collection = "loyaltyvouchers";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
    
    }
	
}