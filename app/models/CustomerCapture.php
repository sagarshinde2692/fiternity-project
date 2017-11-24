<?php

/** 
 * ModelName : CustomerCapture.
 * Maintains a list of functions used for CustomerCapture.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class CustomerCapture extends \Basemodel {

	protected $collection = "customercaptures";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}