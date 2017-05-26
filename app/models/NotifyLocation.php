<?php

/** 
 * ModelName : NotifyLocation.
 * Maintains a list of functions used for NotifyLocation.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

class NotifyLocation extends  \Basemodel {

	protected $collection = "notifylocations";
	
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}



}