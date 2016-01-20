<?php

class Ratecard extends \Basemodel {

	protected $collection = "ratecards";

	public static $rules = array(
		'name' => 'required',
		'duration' => 'required',
		'price' => 'required|numeric',
		'special_price' => 'numeric',
		'location_id' => 'required',
		'findercategory_id' => 'required',
		'interest' => 'required',
		'area' => 'required',
		//'short_description' => 'required',
		);

	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}
	
	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function reviews(){
		return $this->hasMany('Finder', 'finder_id');
	}

	public function serviceoffers(){
		return $this->hasMany('Serviceoffer','ratecard_id');
	}

	public function service(){
		return $this->belongsTo('Service');
	}	

}