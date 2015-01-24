<?php

class Country extends \Basemodel {

	protected $collection = "countries";

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