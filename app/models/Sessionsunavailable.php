<?php

class Sessionsunavailable extends \Basemodel {

	protected $collection = "sessionsunavailable";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}