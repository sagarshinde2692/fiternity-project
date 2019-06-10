<?php

class Myreward extends \Basemodel {

	protected $collection = "myrewards";

	protected $dates = array('success_date');

	public function setCustomerIdAttribute($value){
		
		$this->attributes['customer_id'] = intval($value);
    }

    public function rewardcategory(){
		return $this->belongsTo('Rewardcategory', 'rewardcategory_id');
    }
	
}