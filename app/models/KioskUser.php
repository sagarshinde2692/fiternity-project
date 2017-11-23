<?php

/** 
 * ModelName : KioskUser.
 * Maintains a list of functions used for KioskUser.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class KioskUser extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "users";

	public function setIdAttribute($value){

		$this->attributes['_id'] = $value;
	}
}