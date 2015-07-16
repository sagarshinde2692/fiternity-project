<?php


class Capture extends \Basemodel {
	
	protected $collection = "captures";


	protected $dates = array('followup_date', 'followup_date_time');
	
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
 
}