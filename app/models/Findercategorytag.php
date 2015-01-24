<?php

/** 
 * ModelName : Categorytag.
 * Maintains a list of functions used for Categorytag.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Findercategorytag extends  \Basemodel{

	protected $collection = "findercategorytags";

	public static $rules = array(
		'name' => 'required'
		);

	public function offerings(){

		return $this->hasMany('Offering','categorytag_id','_id');
	}

	public function finders(){

		return $this->belongsToMany('Finder', null, 'categorytags', 'finders');
	}


	public function cities(){

		return $this->belongsToMany('City', null, 'categorytags', 'cities');
	}


}