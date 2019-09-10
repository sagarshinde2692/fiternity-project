<?php
 class FitnessForceAPILog extends \Basemodel {
	protected $collection = "fitnessforceapilogs";

    public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}