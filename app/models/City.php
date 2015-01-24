<?php

class City extends \Basemodel {

	protected $collection = "cities";

	public static $rules = array(
		'name' => 'required'
		);

	public function country(){
		return $this->belongsTo('Country','country_id');
	}


	public function findercategorys(){
		return $this->belongsToMany('Findercategory', null, 'cities', 'findercategorys');
	}

	public function categorytags(){
		return $this->belongsToMany('Findercategorytag', null, 'cities', 'categorytags');
	}


	public function locations(){
		return $this->belongsToMany('Location', null, 'cities', 'locations');
	}


	public function locationtags(){
		return $this->belongsToMany('Locationtag', null, 'cities', 'locationtags');
	}

	public function finders(){
		return $this->hasMany('Finder','country_id');
	}

}