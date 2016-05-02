<?php

class Findercollection extends \Basemodel {

	protected $collection = "findercollections";

	public static $rules = [
		'city_id' => 'required|numeric',
		'name' => 'required',
		'finder_ids' => 'required'
	];

	
	public function finders(){

		return $this->belongsToMany('Finder', null, 'categorytags', 'finders');
	}

	public function city(){
		return $this->belongsTo('City');
	}


}