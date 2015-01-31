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
		'finder_logo' => 'mimes:jpeg,png|image|max:2000',
		'finder_coverimage' => 'mimes:jpeg,png|image|max:2000'
	];


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
		//return $this->belongsToMany('Findercategorytag', null, 'finders', 'categorytags')->select("_id","name","offering_header","slug");
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

}