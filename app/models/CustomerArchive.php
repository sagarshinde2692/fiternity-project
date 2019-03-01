<?php

/** 
 * ModelName : customerarchive.
 * Maintains a the old data from the customer collection.
 */

class CustomerArchive extends \Basemodel {
    protected $collection = "customerarchive";	
    public function setIdAttribute($value){	
		$this->attributes['_id'] = $value;
	}
}