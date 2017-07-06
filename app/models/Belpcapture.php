<?php
/** 
 * ModelName : Belp.
 * Maintains a list of functions used for Belp.
 *
 */
class Belpcapture extends  \Basemodel {
	
	protected $collection = "belpcapture";
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}