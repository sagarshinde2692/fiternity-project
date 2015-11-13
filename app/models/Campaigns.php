<?php

class Campaigns extends \Basemodel {

	protected $collection = "campaigns";

	public static $rules = [
		//'city_id' => 'required|numeric|unique:homepages'
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City')->select('name','slug','country_id','status');
	}
	public function categories(){
		return $this->hasMany('Findercategory');
	}

}