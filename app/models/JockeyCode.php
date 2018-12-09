<?php

class JockeyCode extends  \Basemodel {

	
	protected $collection = "jockeycodes";
	
    public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}