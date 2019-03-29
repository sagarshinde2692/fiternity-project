<?php

class ShedEatNotify extends \Basemodel {

	protected $collection = "shedeatnotify";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}