<?php

class Appinstall extends \Basemodel {

	protected $collection = "appinstall";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}