<?php

/** 
 * ModelName : Feedback.
 * Maintains a list of functions used for Feedback.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class Feedback extends \Basemodel {

	protected $collection = "feedbacks";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}