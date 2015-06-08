<?php

/** 
 * ModelName : Comment.
 * Maintains a list of functions used for Comment.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Comment extends  \Basemodel {

	protected $collection = "comments";
	
	// Add your validation rules here
	public static $rules = [
	    'blog_id' => 'required|integer|numeric',
	    'customer_id' => 'required|integer|numeric',
	    'description' => 'required'
	];

	public function blog(){
		return $this->belongsTo('Blog');
	}

	public function customer(){
		return $this->belongsTo('Customer');
	}

}