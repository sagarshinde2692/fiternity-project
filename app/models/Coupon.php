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
	
	public function getActiveVendorCouponViaRatecard($campaignId, $type){

		if(in_array($type, ['membership', 'memberships', 'extended validity', 'extended_validity'])){
			$type = 'vendor_coupon';
		}
		else {
			$type = 'pps_coupon';
		}

		$coupons = Coupon::raw(function($collection) use ($campaignId, $type) {
			$matchClause = [
				'start_date'=>['$lte' => new \MongoDate()], 
				'end_date'=>['$gt' => new \MongoDate()], 
				'status' => '1',
				'campaign.campaign_id' => $campaignId,
			];
			$matchClause['campaign.'.$type] = '1';
			$aggregate = [
				['$match' => $matchClause]
			];
			
			$ppsHeroCoupon = [
				'campaign.pps_coupon' => '1'
			];
			$vendorHeroCoupon = [
				'campaign.hero_coupon' => '1'
			];
			$nonPPSHeroCoupon = [
				'campaign.pps_coupon' => ['$ne' => '1']
			];
			$nonVendorHeroCoupon = [
				'campaign.hero_coupon' => ['$ne' => '1']
			];
			$facet = [
				'hero_coupons' => [
					['$match' => $vendorHeroCoupon],
					['$project' => ['code' => 1, 'description' => 1]]
				]
			];
			$facet['other_coupons'] = [['$match' => $nonVendorHeroCoupon], ['$project' => ['code' => 1, 'description' => 1]]];
			if($type=='pps_coupon') {
				$facet['hero_coupons'][0]['$match'] = $ppsHeroCoupon;
				$facet['other_coupons'][0]['$match'] = $nonPPSHeroCoupon;
			}
			array_push($aggregate,['$facet' => $facet]);
			return $collection->aggregate($aggregate);
		});
		$coupons = $coupons['result'];
		$finalArray = $coupons[0]['hero_coupons'];
		$finalArray = array_merge($finalArray, $coupons[0]['other_coupons']);
		return $finalArray;
	}
}