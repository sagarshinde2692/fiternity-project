<?php

/** 
 * ModelName : Facility.
 * Maintains a list of functions used for Facility.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Facility extends \Moloquent {

	// Add your validation rules here
	public static $rules = [
		'name' => 'required'
	];

	// Don't forget to fill this array
	//protected $fillable = [];

	protected $guarded = array();


	public function finders(){

		return $this->belongsToMany('Finder', null, 'finders', 'facilities');
	}


	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

}