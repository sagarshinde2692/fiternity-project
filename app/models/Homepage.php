<?php

class Homepage extends \Basemodel {

	protected $collection = "homepages";

	public static $rules = [
		//'city_id' => 'required|numeric|unique:homepages'
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City');
	}

}