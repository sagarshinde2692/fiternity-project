<?php

/** 
 * ModelName : TrainerSlotBooking.
 * Maintains a list of functions used for TrainerSlotBooking.
 *
 * @author Mahesh jadhav <maheshjadhav@fitternity.com>
 */

class TrainerSlotBooking extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "trainerslotbookings";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $dates = array("datetime");

}