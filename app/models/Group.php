<?php

/** 
 * ModelName : Group.
 * Maintains a list of functions used for Group.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Group extends \Basemodel {

	protected $collection = "groups";

	// Add your validation rules here
	public static $rules = [	
	'name'    			   => 'required',
	'group_permissions'    => 'required'
	];

	public function permissions(){
		return $this->embedsMany('Permission');
	}
}