<?php

/** 
 * ModelName : Servicetrainer.
 * Maintains a list of functions used for Servicetrainer.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Servicetrainer extends \Basemodel {

	protected $collection = "servicetrainers";

	public static $rules = array(
		'name' => 'required'
		);

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function services(){
		
		return $this->hasMany('Service', 'trainer_id');
	}

	public function finder(){

		return $this->belongsTo('Finder','finder_id');
	}



	
}