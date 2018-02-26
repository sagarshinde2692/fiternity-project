<?php

/** 
 * ModelName : couponcode.
 * Maintains a list of functions used for couponcode.
 *
 * @author Sanjay Sahu <mjmjadhav@gmail.com>
 */


class Fitcashcoupon extends \Basemodel {

	protected $collection = "fitcashcoupons";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}