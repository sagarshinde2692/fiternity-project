<?php
/** 
 * ModelName : Belp.
 *
 */
class Belptracking extends  \Basemodel {
	
	protected $collection = "belptracking";
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}