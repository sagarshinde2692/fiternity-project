<?php

class Schedulebooktrial extends \Basemodel {

	protected $collection = "schedulebooktrials";

	public static $rules = [
		'name'    => 'required', 
	];

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function scheduleservice(){
		return $this->belongsTo('Finder');
	}	
}