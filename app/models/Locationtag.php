<?php

/** 
 * ModelName : Locationtag.
 * Maintains a list of functions used for Locationtag.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Locationtag extends \Basemodel{

	public static $rules = array(
		'name' => 'required'
		);

	public function finders(){

		return $this->belongsToMany('Finder', null, 'locationtags', 'finders');
	}

	public function cities(){

		return $this->belongsToMany('City', null, 'locationtags', 'cities');
	}
}