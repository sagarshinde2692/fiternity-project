<?php

/** 
 * ModelName : Trainer.
 * Maintains a list of functions used for Trainer.
 *
 * @author Mahesh jadhav <maheshjadhav@fitternity.com>
 */

class Trainer extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "trainers";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $dates = array("datetime");

}