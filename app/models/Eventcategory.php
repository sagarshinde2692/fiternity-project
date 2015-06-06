<?php

class Eventcategory extends Basemodel {

	protected $collection = "eventcategories";

	// Add your validation rules here
	public static $rules = array(
		'name' => 'required'
	);

	
	public function events(){
		
		return $this->hasMany('Event');
	}



}