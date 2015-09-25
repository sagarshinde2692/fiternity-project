<?php

class Servicehomepage extends \Basemodel {

	protected $collection = "servicehomepages";

	public static $rules = [
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City');
	}

}