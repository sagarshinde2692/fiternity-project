<?php

class AssistanceQuestion extends \Basemodel {

	protected $collection = "assistancequestions";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

}