<?php

class Feedback extends \Basemodel {

	protected $collection = "feedback";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}
	public function booktrial(){
		return $this->hasMany('Booktrial','booktrial_id');
	}
	public function customer(){
		return $this->hasMany('Customer','customer_id');
	}
	public function service(){
		return $this->hasMany('Service','service_id');
	}

}