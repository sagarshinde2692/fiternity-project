<?php

class CustomerHealthInfo extends \Basemodel {

	protected $collection = "customerhealthinfos";

	public function setCustomerIdAttribute($value){
		
		$this->attributes['customer_id'] = intval($value);
	}
	
}