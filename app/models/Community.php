<?php

/** 
 * ModelName : Community.
 * Maintains a list of functions used for Community.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Community extends \Moloquent {

	// Add your validation rules here
	public static $rules = [
		// 'title' => 'required'
	];

	// Don't forget to fill this array
	protected $fillable = [];

	protected $guarded = array();

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

}