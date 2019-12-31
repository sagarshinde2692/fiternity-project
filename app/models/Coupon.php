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
    
    public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

	public function getActiveVendorCoupon($coupon_condition){
        return Coupon::where($coupon_condition)
                        ->where("start_date", "<=" , new \DateTime())
                        ->where("end_date", ">=" , new \DateTime())
                        ->get();
	}
}