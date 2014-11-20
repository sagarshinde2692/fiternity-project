<?php

class Permission extends \Basemodel {

	protected $collection = "permissions";

	// Add your validation rules here
	public static $rules = [	
		'name'    => 'required', 
		'action'    => 'required', 
		'uri'    => 'required'
	];


}