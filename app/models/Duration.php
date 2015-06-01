<?php

class Duration extends \Basemodel {

	protected $collection = "durations";

	public static $rules = array(
		'name' => 'required'
		);

	public function cities(){

		return $this->hasMany('City','country_id');
	}

	public function finders(){

		return $this->hasMany('Finder','country_id');
	}

}