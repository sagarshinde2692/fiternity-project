<?php

class Tempbooktrial extends \Basemodel {


	protected $collection = "tempbooktrials";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}


}