<?php

/** 
 * ModelName : Coupons.
 * Maintains a list of functions used for Coupons.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class Coupon extends \Basemodel {
	protected $collection = "coupons";

	protected $dates = array('start_date','end_date');

	public function scopeActive ($query){
		return $query->where('status','=','1');
	}
}