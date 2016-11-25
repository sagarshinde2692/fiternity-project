<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class VendorFeedback extends  \Moloquent {

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	protected $fillable = ['vendor_id','feedback','shared_with','type','value_generated','hidden','created_at','updated_at','date'];

	protected $connection = 'mongodb2';
	
	protected $collection = "vendorfeedbacks";

	protected $dates = array('date');

}