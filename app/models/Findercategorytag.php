<?php

/** 
 * ModelName : Categorytag.
 * Maintains a list of functions used for Categorytag.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Findercategorytag extends \Moloquent {

	protected $collection = "findercategorytags";

	// Add your validation rules here
	public static $rules = [
							'name' => 'required'
					];

	// Don't forget to fill this array
	//protected $fillable = [];

	protected $guarded = array();

	public function offerings(){

		return $this->hasMany('Offering','categorytag_id','_id');
	}

	public function finders(){

		return $this->belongsToMany('Finder', null, 'finders', 'categorytags');
	}

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

}