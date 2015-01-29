<?php

/** 
 * ModelName : Offering.
 * Maintains a list of functions used for Offering.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Offering extends \Basemodel {

	protected $collection = "offerings";

	// Add your validation rules here
	public static $rules = [
	'name' => 'required'
	];

	public function categorytag(){
		
		return $this->belongsTo('Findercategorytag');
	}

	public function finders(){

		return $this->belongsToMany('Finder', null, 'finders', 'offerings');
	}

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

}

