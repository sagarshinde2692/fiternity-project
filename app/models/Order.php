<?php

class Order extends \Basemodel {

	protected $collection = "orders";


	public function setCityIdAttribute($value){
		
		$this->attributes['city_id'] = intval($value);
	}


	public function setServiceIdAttribute($value){
		
		$this->attributes['service_id'] = intval($value);
	}


	public function setFinderIdAttribute($value){
		
		$this->attributes['finder_id'] = intval($value);
	}

	

}