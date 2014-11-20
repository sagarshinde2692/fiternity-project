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
		'lon' => 'required'
	];


	public function user(){
		
		return $this->belongsTo('User');
	}

	public function category(){

		return $this->belongsTo('Findercategory');
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

}