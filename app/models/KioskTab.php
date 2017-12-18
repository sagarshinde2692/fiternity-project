<?php

/** 
 * ModelName : KioskTab.
 * Maintains a list of functions used for KioskTab.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class KioskTab extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "kiosk_tab";

	public function setIdAttribute($value){

		$this->attributes['_id'] = $value;
	}
}