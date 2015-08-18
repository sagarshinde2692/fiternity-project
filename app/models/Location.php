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

	public function setLocationclusterIdAttribute($value){
		$this->attributes['locationcluster_id'] = intval($value);
	}
	
	public function locationcluster(){
		return $this->belongsTo('Locationcluster');
	}	

	public function finders(){
		return $this->hasMany('Finder');
	}

	public function fitmaniadods(){
		return $this->hasMany('Fitmaniadod');
	}

	public function ratecards(){
		return $this->hasMany('Ratecard','location_id');
	}

	public function cities(){
		return $this->belongsToMany('City', null, 'locations', 'cities');
	}
	
}