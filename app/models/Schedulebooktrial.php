<?php

class Schedulebooktrial extends \Basemodel {

	public static $rules = [
		'name'    => 'required', 
	];

	public function scheduleservice(){
		return $this->belongsTo('Finder');
	}	
}