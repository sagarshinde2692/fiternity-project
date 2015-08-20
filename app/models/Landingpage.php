<?php

/** 
 * ModelName : Landingpage.
 * Maintains a list of functions used for Landingpage.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Landingpage extends \Basemodel {

	protected $collection = "landingpages";

	public static $rules = [
	'name' => 'required'
	];

	public function setLocationclusterIdAttribute($value){
		$this->attributes['locationcluster_id'] = intval($value);
	}
	
	public function locationcluster(){
		return $this->belongsTo('Locationcluster');
	}	

}