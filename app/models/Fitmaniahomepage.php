<?php

class Fitmaniahomepage extends \Basemodel {

	protected $collection = "fitmaniahomepages";

	public static $rules = [
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City');
	}

}