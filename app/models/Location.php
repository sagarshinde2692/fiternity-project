<?php

/** 
 * ModelName : Location.
 * Maintains a list of functions used for Location.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Location extends \Basemodel {

	protected $collection = "locations";

	public static $rules = [
		'name' => 'required'
	];

	public function finders(){
		
		return $this->hasMany('Finder');
	}

	public function cities(){

		return $this->belongsToMany('City', null, 'locations', 'cities');
	}


	public function fitmaniadods(){
		return $this->hasMany('Fitmaniadod');
	}


	
}