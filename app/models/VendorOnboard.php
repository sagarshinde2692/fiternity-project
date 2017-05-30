<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class VendorOnboard extends  \Basemodel {

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $fillable = ['vendor_id','brand_usp','current_marketing_initiatives','discount_interested','discount_upto','event_collaboration_interested','offline_marketing_interested','primary_aim','remark','target_age_group','update_frequency','hidden','created_at','updated_at'];

	protected $connection = 'mongodb2';
	
	protected $collection = "vendoronboards";

}