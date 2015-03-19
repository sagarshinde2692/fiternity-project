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
		'short_description' => 'required',
		);

	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function findercateogry(){
		return $this->belongsTo('Findercategory');
	}

	public function location(){
		return $this->belongsTo('Location');
	}

}