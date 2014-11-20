<?php

/** 
 * ModelName : Event.
 * Maintains a list of functions used for Event.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

namespace App\Models;

class Event extends \Moloquent {

	protected $collection = "events";

	protected $dates = array('start_date','end_date');

	// Add your validation rules here
	public static $rules = [
		 'title' => 'required'
	];

	// Don't forget to fill this array
	protected $fillable = [];

	protected $guarded = array();


	public function category(){

		return $this->belongsTo('Eventcategory');
	}	

	public function categorytags(){

		return $this->belongsToMany('Eventcategorytag', null, 'events', 'categorytags');
	}

	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

}