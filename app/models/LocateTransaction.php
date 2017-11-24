<?php

/** 
 * ModelName : LocateTransaction.
 * Maintains a list of functions used for LocateTransaction.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class LocateTransaction extends \Basemodel {

	protected $collection = "locatetransactions";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}