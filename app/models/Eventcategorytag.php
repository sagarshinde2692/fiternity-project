<?php

class Eventcategorytag extends \Basemodel {

	
	protected $collection = "eventcategorytags";

	public static $rules = array(
		'name' => 'required'
		);

	
	public function events(){

		return $this->belongsToMany('Event', null, 'events', 'categorytags');
	}

}