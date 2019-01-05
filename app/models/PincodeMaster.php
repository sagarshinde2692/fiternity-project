<?php

class PincodeMaster extends \Basemodel {

	protected $collection = "pincode_master";
    protected $connection = "mongodb2";

	// Add your validation rules here
	public static $rules = [	
		'pincode'    => 'required', 
		'state_code'    => 'required', 
		'city_name'    => 'required'
	];


}