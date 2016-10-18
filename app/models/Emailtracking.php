<?php

/** 
 * ModelName : Emailtracking.
 * Maintains a list of functions used for Emailtracking.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class Emailtracking extends  \Basemodel {
	
	protected $collection = "emailtrackings";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}
