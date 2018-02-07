<?php

class Customergroup extends \Basemodel {

	protected $collection = "customergroups";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}