<?php

/** 
 * ModelName : Coupons.
 * Maintains a list of functions used for Coupons.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class Nocouponcodeoffers extends \Basemodel {
	protected $collection = "nocouponcodeoffers";

	public function getActiveVendorNoCouponOffer($campaign_id){
		return Nocouponcodeoffers::select("code","description","long_desc")
						->where(array("type"=>"vendor","status" => "1","campaign_id" => "$campaign_id"))
						->get();
	}
}