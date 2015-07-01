<?php

class Basemodel extends \Moloquent {


	protected $guarded = array();
	

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}
	
	public function scopeActive ($query){

		return $query->where('status','=','1');
	}



}