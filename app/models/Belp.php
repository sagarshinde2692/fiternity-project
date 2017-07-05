<?php
/** 
 * ModelName : Belp.
 * Maintains a list of functions used for Belp.
 *
 */
class Belp extends  \Basemodel {
	
	protected $collection = "belp";
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}