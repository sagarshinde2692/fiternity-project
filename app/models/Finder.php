<?php

/** 
 * ModelName : Finder.
 * Maintains a list of functions used for Finder.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Finder extends  \Basemodel {

	protected $collection = "finders";
	
	// Add your validation rules here
	public static $rules = [
	'title' => 'required',
	'lat' => 'required',
	'lon' => 'required',
	'country_id' => 'required',
	'city_id' => 'required',
	'location_id' => 'required',
	'category_id' => 'required',
	'finder_logo' => 'mimes:jpeg,png|image|max:2000',
	'finder_coverimage' => 'mimes:jpeg,png|image|max:2000'
	];


	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}

	public function user(){
		return $this->belongsTo('User');
	}

	public function category(){
		return $this->belongsTo('Findercategory');
	}	

	public function country(){
		return $this->belongsTo('Country');
	}	

	public function city(){
		return $this->belongsTo('City');
	}	

	public function location(){
		return $this->belongsTo('Location');
	}	
	
	public function categorytags(){
		return $this->belongsToMany('Findercategorytag', null, 'finders', 'categorytags');
	}

	public function locationtags(){
		return $this->belongsToMany('Locationtag', null, 'finders', 'locationtags');
	}

	public function offerings(){
		return $this->belongsToMany('Offering', null, 'finders', 'offerings');
	}
	
	public function facilities(){
		return $this->belongsToMany('Facility', null, 'finders', 'facilities');
	}

	public function scheduleservices(){
		return $this->hasMany('Schedulebooktrial');
	}

	public function booktrials(){
		return $this->hasMany('Booktrial','booktrial_id');
	}

	public function servicerates(){
		return $this->hasMany('Ratecard','finder_id');
	}

	public function services(){
		return $this->hasMany('Service','finder_id');
	}

	public function reviews(){
		return $this->hasMany('Review','finder_id');
	}
}