<?php

class Trialalert extends \Basemodel {

	protected $collection = "trialalerts";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}