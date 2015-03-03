<?php

class Schedulebooktrial extends \Basemodel {

	protected $collection = "schedulebooktrials";

	public static $rules = [
		'name'    => 'required', 
	];

	public function scheduleservice(){
		return $this->belongsTo('Finder');
	}	
}