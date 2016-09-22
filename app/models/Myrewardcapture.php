<?php

class Myrewardcapture extends \Basemodel {

	protected $collection = "myrewardcaptures";

	public function setCustomerIdAttribute($value){
		
		$this->attributes['customer_id'] = intval($value);
	}
	
}