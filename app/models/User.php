<?php

/** 
 * ModelName : User.
 * Maintains a list of functions used for User.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class User extends \Basemodel {

	protected $collection = "users";

	// Add your validation rules here
	public static $rules = [
	'name'    => 'required',
	'email'    => 'required|email', 
	'password' => 'required|min:6' ,
	'usergroups'	=> 'required'
	];

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function groups(){
		return $this->embedsMany('Group');
	}

	public function finders(){
		return $this->hasMany('Finder');
	}

	public function blogs(){
		return $this->hasMany('Blog','author_id');
	}

	public function validatedblogs(){
		return $this->hasMany('Blog','expert_id');
	}

}