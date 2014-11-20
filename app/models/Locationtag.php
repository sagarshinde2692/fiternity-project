<?php

/** 
 * ModelName : Locationtag.
 * Maintains a list of functions used for Locationtag.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Locationtag extends \Moloquent {

	// Add your validation rules here
	public static $rules = [
		 'name' => 'required'
	];

	// Don't forget to fill this array
	protected $fillable = [];

	protected $guarded = array();

	public function finders(){

		return $this->belongsToMany('Finder', null, 'finders', 'locationtags');
	}

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}
}