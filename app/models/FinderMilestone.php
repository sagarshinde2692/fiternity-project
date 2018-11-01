<?php

class FinderMilestone extends \Basemodel {

	protected $collection = "findermilestones";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}