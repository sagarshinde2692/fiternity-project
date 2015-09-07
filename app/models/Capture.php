<?php


class Capture extends \Basemodel {
	
	protected $collection = "captures";


	protected $dates = array('followup_date', 'followup_date_time');
	
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
 
 	public function setCaptureStatusAttribute($value){
		
		$this->attributes['capture_status'] = 'yet to connect';
	}

}