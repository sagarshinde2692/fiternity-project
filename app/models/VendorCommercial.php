<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class VendorCommercial extends  \Basemodel {

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $fillable = ['vendor_id','aquired_person','business_type','commercial_type','commision','contract_duration','listing_fee','mou','waiver','waiver_duration','waiver_end_date','waiver_start_date','aquired_date','contract_end_date','contract_start_date','on_board_date','payment_collection_date','hidden','mou_date','created_at','updated_at'];

	protected $connection = 'mongodb2';
	
	protected $collection = "vendorcommercials";

	protected $dates = array("aquired_date","contract_end_date","contract_start_date","on_board_date","payment_collection_date","waiver_end_date","waiver_start_date");

}