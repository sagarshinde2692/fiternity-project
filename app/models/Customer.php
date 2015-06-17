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
	    'email' => 'required|email|unique:customers|max:255',
	    'password' => 'required|min:8|max:20|confirmed',
	    'password_confirmation' => 'required|min:8|max:20',
	    'contact_no' => 'required|size:10',
	    'identity' => 'required'
	];

	public function review(){

		return $this->hasMany('Customer', 'customer_id');
	}

	public function comment(){
		
		return $this->hasMany('Customer', 'customer_id');
	}


}