<?php

class Paylater extends \Basemodel {

	protected $collection = "paylaters";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}