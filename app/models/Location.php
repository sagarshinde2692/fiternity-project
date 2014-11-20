<?php

/** 
 * ModelName : Location.
 * Maintains a list of functions used for Location.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Location extends \Moloquent {

	protected $collection = "locations";

	// Add your validation rules here
	public static $rules = [
		'name' => 'required'
	];

	protected $guarded = array();

	public function finders(){
		
		return $this->hasMany('Finder');
	}

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}
}