<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class VendorBdresearch extends  \Basemodel {

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $fillable = ['vendor_id','bd_name','remark','conversation_date','hidden','created_at','updated_at'];

	protected $connection = 'mongodb2';
	
	protected $collection = "vendorbdresearchs";

	protected $dates = array('conversation_date');

}