<?php

/** 
 * ModelName : Fitmaniadod.
 * Maintains a list of functions used for Fitmaniadod.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Fitmaniadod extends \Basemodel {

	protected $collection = "fitmaniadods";

	protected $dates = array('offer_date');

	public static $rules = array(
		'name' => 'required',
		'finder_id' => 'required',
		'price' => 'required',
		'ordering' => 'required',
		'offer_date' => 'required'
	);
	
	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}

	public function setFinderIdAttribute($value){
		$this->attributes['finder_id'] = intval($value);
	}

	public function setLocationIdAttribute($value){
		$this->attributes['location_id'] = intval($value);
	}

	public function setCityIdAttribute($value){
		$this->attributes['city_id'] = intval($value);
	}

	public function setPriceAttribute($value){
		$this->attributes['price'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}

	public function location(){
		return $this->belongsTo('Location');
	}	

	public function city(){
		return $this->belongsTo('City');
	}


}