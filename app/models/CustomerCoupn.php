<?php

/** 
 * ModelName : CustomerCoupn.
 * Maintains a list of functions used for CustomerCoupn.
 *
 * @author Mahesh Jadhav
 */

class CustomerCoupn extends  \Basemodel {

	protected $collection = "customercoupns";
	
	// Add your validation rules here
	public static $rules = [
		'customer_id' => 'required|integer|numeric',
		'amount' => 'required|integer|numeric',
	];

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}



}