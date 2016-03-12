<?php

class Temp extends \Basemodel {


	protected $collection = "temps";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}


}