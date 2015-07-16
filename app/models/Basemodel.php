<?php

class Basemodel extends \Moloquent {


	protected $guarded = array();
	
	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}


}