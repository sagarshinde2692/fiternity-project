<?php

/** 
 * ModelName : Customer.
 * Maintains a list of functions used for Customer.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Customer extends  \Basemodel {

	protected $collection = "customers";
	protected $dates = array('last_visited');
	
	// Add your validation rules here
	public static $rules = [
	    'name' => 'required|max:255',
	    'email' => 'required|email|unique:customers|max:255',
	    'password' => 'required|min:6|max:20|confirmed',
	    'password_confirmation' => 'required|min:6|max:20',
	    'contact_no' => 'max:15',
	    'identity' => 'required'
	];

	public static $update_rules = [
	    'name' => 'max:255',
	    'email' => 'email|max:255',
	    'contact_no' => 'max:15',
	    'location' => 'max:255'
	];

	public function reviews(){

		return $this->hasMany('Customer', 'customer_id');
	}

	public function comments(){
		
		return $this->hasMany('Customer', 'customer_id');
	}


}