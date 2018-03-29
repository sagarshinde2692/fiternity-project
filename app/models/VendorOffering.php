<?php

/** 
 * ModelName : Vendor Offering FITAPI.
 * Maintains a list of functions used for Offering.
 *
 * @author shahaan.syed
 */


class VendorOffering extends \Basemodel {

	protected $collection = "offerings";

	// Add your validation rules here
	public static $rules = [
	'name' => 'required'
	];

	public function categorytag(){
		
		return $this->belongsTo('Findercategorytag');
	}
	protected $connection = 'mongodb2';

	public function finders(){

		return $this->belongsToMany('Finder', null, 'finders', 'offerings');
	}

	public function scopeActive ($query){

		return $query->where('hidden','=',false);
	}

}

