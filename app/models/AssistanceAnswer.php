<?php

class AssistanceAnswer extends \Basemodel {

	protected $collection = "assistanceanswers";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

}