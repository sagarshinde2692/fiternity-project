<?php


/** 
 * ModelName : Locationcluster.
 * Maintains a list of functions used for Locationcluster.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Locationcluster extends \Basemodel {

	protected $collection = "locationclusters";

	public static $rules = [
		'name' => 'required',
		'city_id' => 'required|numeric'
	];

	public function setCityIdAttribute($value){
		$this->attributes['city_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}

	public function finders(){
		return $this->hasMany('Finder');
	}

	public function locations(){
		return $this->hasMany('Location','locationcluster_id');
	}

	public function city(){
		return $this->belongsTo('City');
	}	

	public function landingpages(){
		return $this->hasMany('Landingpage');
	}

}