<?php

class Appinstall extends \Basemodel {
	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	protected $collection = "appinstall";


}