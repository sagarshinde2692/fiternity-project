<?php 
namespace App\Services;

use ___PHPSTORM_HELPERS\object;
use Coupon;
use Nocouponcodeoffers;
use Config;
use Input;
use App\Services\Utilities as Utilities;
use Ratecard;
use App\Services\CustomerReward as CouponReward;
use Order;
use Pass;
use Zend\Validator\InArray;

Class CouponService {
    protected $appliedCouponData =  array();
    public function __construct() {
        $this->customerreward = new CouponReward();
        $this->utilities = new Utilities();
    }

    public function addcoupoun($services_ratecard,$vendor_page_without_login= null,$request_from=null,$finder=null){
        // $campaign_data = $this->utilities->getCampaignData();

        // if(empty($campaign_data)){
        //     return false;
        // }
        // $couponObj =  new Coupon();
        // $nocouponcodeofferobj =  new Nocouponcodeoffers();
        $campaign_id = Config::get('app.config_campaign_id');//$campaign_data->_id;
        // $coupon_condition  =  array("campaign.vendor_coupon"=>"1","campaign.hero_coupon" =>"1","status" => "1","campaign.campaign_id" => "$campaign_id");
        // $coupon_data = $couponObj->getActiveVendorCoupon($coupon_condition);
        $vendor_type =  "vendor_page";
        $all_coupon_data = $this->getActiveCouponsByType($campaign_id,$vendor_type);
        if(!empty($all_coupon_data) && count($all_coupon_data)>0) {
            $coupon_data[] = $all_coupon_data[0];
            unset($all_coupon_data[0]);
        }
        //unsetting the hero coupon array from all coupons data
        if(empty($coupon_data) && !isset($coupon_data[0]['campaign'][0]['hero_coupon'])){
            return false;
        } else if($coupon_data[0]['campaign'][0]['hero_coupon'] != "1"){
            return false;
        }
        // $other_vendor_coupons =  array("campaign.vendor_coupon"=>"1","campaign.hero_coupon" =>"0","status" => "1","campaign.campaign_id" => "$campaign_id");
        // $other_vendor_coupons_data = $couponObj->getActiveVendorCoupon($other_vendor_coupons);
        $other_vendor_coupons_data = $all_coupon_data;

        // $noCouponOffersData = $nocouponcodeofferobj->getActiveVendorNoCouponOffer($campaign_id);
        $noCouponOffersData = $this->getActiveNoCouponOffersByType($campaign_id,$vendor_type);
        
        $temp_coupon = array();
        if($request_from == "app"){
            return $this->couponAppliedForApp($coupon_data,$services_ratecard,$noCouponOffersData,$vendor_page_without_login,$finder,$other_vendor_coupons_data);
        }
        return $this->couponAppliedForWeb($coupon_data,$services_ratecard,$noCouponOffersData,$vendor_page_without_login,$finder,$other_vendor_coupons_data);
    }

    private function couponAppliedForWeb($coupon_data,$services_ratecard,$noCouponOffersData,$vendor_page_without_login,$finder,$other_vendor_coupons_data){
        $originalRateCard =  array();
        $isCouponAppliedFlag= false;
        foreach($coupon_data as $cval){
            foreach($services_ratecard as $srkey => $srval) {
                foreach($srval['serviceratecard'] as $ratecard) {
                    $response="";
                    if(in_array($ratecard['type'], ['membership', 'memberships', 'extended validity', 'extended_validity'])){
                        $response=  $this->customerreward->verifyCouponFinderDetail($ratecard, $cval['code'],null, null, null, null, null, null, null, null, $corporate_discount_coupon = true,$vendor_page_without_login, $cval, $finder, $srval);
                        if(!isset($response['error_message'])) {
                            $isCouponAppliedFlag = true;
                            $temp_coupon['price'][] = array($ratecard['_id'] => $response['data']['final_amount']);
                        }
                    }
                    $discount_price = ($ratecard['special_price'] > 0)?$ratecard['special_price']:$ratecard['price'];
                    $originalRateCard['price'][] = array($ratecard['_id'] => $discount_price);
                } 
            }
            if($isCouponAppliedFlag) {
                $temp_coupon['coupon_code'] = strtoupper($cval['code']);
                $temp_coupon['desc'] = $cval['description'];
                if(isset($cval['long_description']) && !empty($cval['long_description'])) {
                    $temp_coupon['long_desc']= $cval['long_description'];
                }
                $temp_coupon['coupon_discount'] = $cval['discount_percent'];
                if(isset($cval['campaign'][0]['vendor_coupon'])) {
                    $temp_coupon['default_view'] = $cval['campaign'][0]['vendor_coupon'];
                }
                if(isset($cval['campaign'][0]['hero_coupon']) && $cval['campaign'][0]['hero_coupon']==1 ){
                    $temp_coupon['default_selected'] = $cval['campaign'][0]['hero_coupon'];
                }
                $this->appliedCouponData[] =  $temp_coupon;
                unset($temp_coupon);
            }
        }

        foreach($other_vendor_coupons_data as $ovcval) {
            $ovtemp_coupon['coupon_code'] = strtoupper($ovcval['code']);
            $ovtemp_coupon['desc'] = $ovcval['description'];
            if(isset($ovcval['long_description']) && !empty($ovcval['long_description'])){
                $ovtemp_coupon['long_desc']= $ovcval['long_description'];
            }
            $this->appliedCouponData[] =  $ovtemp_coupon;
            unset($ovtemp_coupon);
        }

        foreach($noCouponOffersData as $nco_val){
            $this->appliedCouponData[] = array(
               'coupon_code' => strtoupper($nco_val['code']),
                'desc' => $nco_val['description'],
                'long_desc' => $nco_val['long_description'],
            ); 
        }
        if($isCouponAppliedFlag){
            $this->appliedCouponData[] = $originalRateCard;
        }
        return $this->appliedCouponData;
    }

    private function couponAppliedForApp($coupon_data,$services_ratecard,$noCouponOffersData,$vendor_page_without_login,$finder,$other_vendor_coupons_data){
        $offers = array("offers" => array(
                "headers" => "Available Coupons",
                "text" => "View All Offers",
            )
        );
        $services_coupon = array();
        $isCouponAppliedFlag =false;
        foreach($coupon_data as $cval){
            
            foreach($services_ratecard as $srkey => $srval){
                    foreach($srval['ratecard'] as $ratecardKey => $ratecard){
                     if(in_array($ratecard['type'], ['membership', 'memberships', 'extended validity', 'extended_validity'])){
                        $response=  $this->customerreward->verifyCouponFinderDetail($ratecard, $cval['code'],null, null, null, null, null, null, null, null, $corporate_discount_coupon = true,$vendor_page_without_login, $cval, $finder, $srval);
                        if(!isset($response['error_message'])) {
                          $isCouponAppliedFlag = true;
                          $services_coupon[$ratecard['_id']]['coupons'][$cval['_id']] =  $response['data']['final_amount'];
                        }
                     }    
                    }    
            }
            if($isCouponAppliedFlag){
            $temp_coupon["_id"]= $cval['_id'];
            $temp_coupon['code']= strtoupper($cval['code']);
            $temp_coupon['description']= $cval['description'];
            if(isset($cval['long_description']) && !empty($cval['long_description'])){
                if($this->checkAndroidVersion(['android'=>5.33])){
                    foreach($cval['long_description'] as $ldkey => $val){
                        $temp_coupon['terms'][$ldkey]= "$val <br>";
                    }
                }else {
                $temp_coupon['terms']= $cval['long_description'];
                }
            }
            $temp_coupon['coupon_discount'] = $cval['discount_percent'];
            $temp_coupon["is_applicable"]= true;
            if(isset($cval['campaign'][0]['hero_coupon']) && $cval['campaign'][0]['hero_coupon']=='1' ){
                $temp_coupon['default_selected'] = $cval['campaign'][0]['hero_coupon'];
            }
            $offers['offers']['options'][] = $temp_coupon;
            unset($temp_coupon);
         }
        }
        
        foreach($other_vendor_coupons_data as $ovcval) {

            $ovtemp_coupon['code']= strtoupper($ovcval['code']);
            $ovtemp_coupon['description']= $ovcval['description'];
            if(isset($ovcval['long_description']) && !empty($ovcval['long_description'])){
                if($this->checkAndroidVersion(['android'=>5.33])){
                    foreach($ovcval['long_description'] as $ldkey => $val){
                        $ovtemp_coupon['terms'][$ldkey]= "$val <br>";
                    }
                }else {
                $ovtemp_coupon['terms']= $ovcval['long_description'];
                }
            }
            $offers['offers']['options'][] = $ovtemp_coupon;
            unset($ovtemp_coupon);
        }
        
        foreach($noCouponOffersData as $nco_val){
            $ncotemp_coupon['code']= strtoupper($nco_val['code']);
            $ncotemp_coupon['description']= $nco_val['description'];
            if(isset($nco_val['long_description']) && !empty($nco_val['long_description'])){
                if($this->checkAndroidVersion(['android'=>5.33])){
                    foreach($nco_val['long_description'] as $ldkey => $val){
                        $ncotemp_coupon['terms'][$ldkey]= "$val <br>";
                    }
                }else {
                $ncotemp_coupon['terms']= $nco_val['long_description'];
                }
            }
            $offers['offers']['options'][] = $ncotemp_coupon;
            unset($ncotemp_coupon);
        }
        return array("services_coupon" => $services_coupon,"offers"=> $offers['offers']);
    }

    public function getActiveCouponsByType($campaignId, $type){
		$vendorPageAllCoupons = false;
        $aggregate = [];
        $matchClause = [
            'start_date'=>['$lte' => new \MongoDate()], 
            'end_date'=>['$gt' => new \MongoDate()], 
            'status' => '1',
            'campaign.campaign_id' => $campaignId.'',
        ];
        if($type!='vendor_page') {
            $projection = ['_id' => 0, 'code' => ['$toUpper' => '$code'], 'description' => 1, 'long_description' => 1, 'isApplicable' => ['$literal' => true]];
        }
        if($type=='pass') {
            $matchClause['ratecard_type'] = 'pass';
            $matchClause['type'] = 'pass';
            array_push($aggregate, ['$match' => $matchClause]);
            if(!empty($projection)) {
                array_push($aggregate, ['$project' => $projection]);
            }
        }
		else {
            if($type=='vendor_page') {
                $vendorPageAllCoupons = true;
            }
            if(in_array($type, ['vendor_page', 'membership', 'memberships', 'extended validity', 'extended_validity'])){
                $type = 'vendor_coupon';
            }
            else {
                $type = 'pps_coupon';
            }

            if($vendorPageAllCoupons) {
                $matchClause['$or'] = [
                    ['campaign.vendor_coupon' => ['$eq' => '1']],
                    ['campaign.pps_coupon' => ['$eq' => '1']]
                ];
            }
            else {
                $matchClause['campaign.'.$type] = '1';
            }
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
                    ['$match' => $vendorHeroCoupon]
                ]
            ];
            if(!empty($projection)) {
                array_push($facet['hero_coupons'], ['$project' => $projection]);
                $facet['other_coupons'] = [['$match' => $nonVendorHeroCoupon], ['$project' => $projection]];
            }
            // $facet['other_coupons'] = [['$match' => $nonVendorHeroCoupon]];
            if($type=='pps_coupon') {
                $facet['hero_coupons'][0]['$match'] = $ppsHeroCoupon;
                $facet['other_coupons'][0]['$match'] = $nonPPSHeroCoupon;
            }
            array_push($aggregate,['$facet' => $facet]);
        }
        // return $aggregate;
		$coupons = Coupon::raw(function($collection) use ($aggregate) {
			return $collection->aggregate($aggregate);
		});
        $coupons = $coupons['result'];
        if($type=='pass') {
            $finalArray = $coupons;
        }
        else {
            $finalArray = $coupons[0]['hero_coupons'];
            if(!empty($coupons[0]['other_coupons'])) {
                $finalArray = array_merge($finalArray, $coupons[0]['other_coupons']);
            }
        }
		return $finalArray;
	}
    public function getActiveNoCouponOffersByType($campaignId, $type){
        $aggregate = [];
        if($type=='all') {
            $type = ['pps', 'membership', 'pass'];
        }
        if($type=='vendor_page') {
            $type = ['pps', 'membership'];
        }
        if(in_array($type,['workout session', 'trial'])) {
            $type = ['pps'];
        }
        if($type=='pass') {
            $type = ['pass'];
        }
        else if(in_array($type, ['membership', 'memberships', 'extended validity', 'extended_validity'])){
            $type = ['membership'];
        }
        $matchClause = [
            'start_date'=>['$lte' => new \MongoDate()], 
            'end_date'=>['$gt' => new \MongoDate()], 
            'status' => '1',
            'campaign.campaign_id' => $campaignId.'',
            'campaign.type' => ['$in' => $type]
        ];
        $projection = ['_id' => 0, 'code' => ['$toUpper' => '$code'], 'description' => 1, 'long_description' => 1];
        $aggregate = [
            ['$match' => $matchClause],
            ['$project' => $projection]
        ];
        // return $aggregate;
		$coupons = Nocouponcodeoffers::raw(function($collection) use ($aggregate) {
			return $collection->aggregate($aggregate);
		});
		$coupons = $coupons['result'];
		// $finalArray = $coupons[0]['hero_coupons'];
		// $finalArray = array_merge($finalArray, $coupons[0]['other_coupons']);
		return $coupons;
	}

    public function getlistvalidcoupons($type = null,$order_id=null,$pass_id=null,$ratecard_id=null){
        // $couponObj =  new Coupon();
        $nocouponcodeofferobj =  new Nocouponcodeoffers();
        $data = [];
        $campaign_id = Config::get('app.config_campaign_id');
        if($type=="pass") {
            $data['type'] = 'pass';
        }
        else if(!empty($ratecard_id)){
            $data = Ratecard::select("type")->where('_id',(int)$ratecard_id)->first();
        }
        $coupon_data = $this->getActiveCouponsByType($campaign_id, $data['type']);
        
        // $noCouponOffersData = $nocouponcodeofferobj->getActiveVendorNoCouponOffer($campaign_id);
        $noCouponOffersData = $this->getActiveNoCouponOffersByType($campaign_id, $data['type']);
        // $temp_coupon = $coupon_data;
        $finalArray = array_merge($coupon_data, $noCouponOffersData);
        return $finalArray;
    }

    public function checkAndroidVersion($data){
        $app_version = \Request::header('App-Version');
        $device_type = \Request::header('Device-Type');
        
        if($device_type == 'android' && $app_version == $data['android']){
            return true;
        }
        return false;
    }
}

?>
