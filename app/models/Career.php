<?php

/** 
 * ModelName : Career.
 * Maintains a list of functions used for Career.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class Career extends \Basemodel {

	protected $collection = "careers";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}