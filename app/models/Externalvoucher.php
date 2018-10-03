<?php

class Externalvoucher extends \Basemodel {

	protected $collection = "externalvouchers";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}