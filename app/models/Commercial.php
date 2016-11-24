<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Commercial extends  \Basemodel {

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $connection = 'mongodb2';
	
	protected $collection = "vendorcommercials";

	protected $dates = array("aquired_date","contract_end_date","contract_start_date","on_board_date","payment_collection_date","waiver_end_date","waiver_start_date");

}