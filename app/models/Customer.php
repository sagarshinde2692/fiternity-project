<?php

/** 
 * ModelName : Customer.
 * Maintains a list of functions used for Customer.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Customer extends  \Basemodel {

	protected $collection = "customers";
	
	// Add your validation rules here
	public static $rules = [
	    'name' => 'required|min:10|max:255',
	    'email' => 'required|email|unique|max:255',
	    'profile_image' => 'required',
	    'identity' => 'required',
	    'verified' => 'required',
	    'description' => 'required'
	];

	public function review(){

		return $this->hasMany('Customer', 'customer_id');
	}

	public function comment(){
		
		return $this->hasMany('Customer', 'customer_id');
	}


}