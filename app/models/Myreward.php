<?php

class Myreward extends \Basemodel {

	protected $collection = "myrewards";

	public function setCustomerIdAttribute($value){
		
		$this->attributes['customer_id'] = intval($value);
	}
	
}