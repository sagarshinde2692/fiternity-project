<?php

class ShedEatRegistration extends \Basemodel {

	protected $collection = "shedeatregistrations";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}