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
	    'description' => 'required'
	];

	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function customer(){
		return $this->belongsTo('Customer');
	}

}