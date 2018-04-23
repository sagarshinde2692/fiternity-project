<?php

class Vendorstripes extends \Basemodel {

	protected $collection = "vendorstripes";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}
