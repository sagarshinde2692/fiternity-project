<?php

/** 
 * ModelName : Vendoruser.
 * Maintains a list of functions used for Vendoruser.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Vendoruser extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "users";


	public function setIdAttribute($value){

		$this->attributes['_id'] = $value;
	}
}