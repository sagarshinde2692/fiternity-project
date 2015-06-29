<?php

/** 
 * ModelName : Review.
 * Maintains a list of functions used for Review.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Review extends  \Basemodel {

	protected $collection = "reviews";
	
	// Add your validation rules here
	public static $rules = [
	    'finder_id' => 'required|integer|numeric',
	    'customer_id' => 'required|integer|numeric',
	    'rating' => 'required|integer|numeric',
	    'detail_rating' => 'required',
	    'description' => 'required'
	];

	protected $appends = array('customer');

	public function getCustomerAttribute(){
		
		$customer = Customer::where('_id',$this->customer_id)->first(array('name','picture','email','contact_no' ));
		return $customer;
	}

	public function finders(){
		return $this->belongsTo('Finder');
	}

	public function customers(){
		return $this->belongsTo('Customer');
	}

}