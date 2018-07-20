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
	    'rating' => 'required|numeric',
	    'detail_rating' => 'required',
	    //'description' => 'required'
	];

	public static $withoutAppends = false;

	public static $setAppends = [];
	

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}

	protected $appends = array('customer');

	public function getCustomerAttribute(){
		$customer = Customer::where('_id',$this->customer_id)->first(array('name','picture'));
		return $customer;
	}

	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function customer(){
		return $this->belongsTo('Customer');
	}


	
}