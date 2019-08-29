<?php

class RazorpayWebhook extends  \Basemodel {
	
	protected $collection = "razorpaywebhook";
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}