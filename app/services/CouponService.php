<?php 
namespace App\Services;

use ___PHPSTORM_HELPERS\object;
use Coupon;
use Nocouponcodeoffers;
use Config;
use Input;
use App\Services\Utilities as Utilities;
use Ratecard;
use App\Services\CustomerReward as customerreward;
use Order;
use Pass;
use Zend\Validator\InArray;

Class CouponService {
    protected $appliedCouponData =  array();
    public function __construct(CustomerReward $customerreward, Utilities $utilities ) {
        $this->customerreward = $customerreward;
        $this->utilities = $utilities;
    }

    public function addcoupoun($services_ratecard,$vendor_page_without_login= null,$request_from=null,$finder=null){
        // $campaign_data = $this->utilities->getCampaignData();

        // if(empty($campaign_data)){
        //     return false;
        // }
        $couponObj =  new Coupon();
        $nocouponcodeofferobj =  new Nocouponcodeoffers();
        $campaign_id = Config::get('app.config_campaign_id');//$campaign_data->_id;
        $coupon_condition  =  array("campaign.vendor_coupon"=>"1","campaign.hero_coupon" =>"1","status" => "1","campaign.campaign_id" => "$campaign_id");
        $coupon_data = $couponObj->getActiveVendorCoupon($coupon_condition);
        if(empty($coupon_data)){
            return false;
        }
        $other_vendor_coupons =  array("campaign.vendor_coupon"=>"1","campaign.hero_coupon" =>"0","status" => "1","campaign.campaign_id" => "$campaign_id");
        $other_vendor_coupons_data = $couponObj->getActiveVendorCoupon($other_vendor_coupons);

        $noCouponOffersData = $nocouponcodeofferobj->getActiveVendorNoCouponOffer($campaign_id);
        
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
            
            foreach($services_ratecard as $srkey => $srval){
                    foreach($srval['serviceratecard'] as $ratecard){
                        $response=  $this->customerreward->verifyCouponFinderDetail($ratecard, $cval['code'],null, null, null, null, null, null, null, null, $corporate_discount_coupon = true,$vendor_page_without_login, $cval, $finder, $srval);
                        if(!isset($response['error_message'])) {
                            $isCouponAppliedFlag = true;
                            $temp_coupon['price'][] = array($ratecard['_id'] => $response['data']['final_amount']);
                        }
                        $discount_price = ($ratecard['special_price'] > 0)?$ratecard['special_price']:$ratecard['price'];
                        $originalRateCard['price'][] = array($ratecard['_id'] => $discount_price);
                    } 
            }
            if($isCouponAppliedFlag){
                $temp_coupon['coupon_code'] = $cval['code'];
                $temp_coupon['desc'] = $cval['description'];
                if(isset($cval['long_description']) && !empty($cval['long_description'])){
                    $temp_coupon['long_desc']= $cval['long_description'];
                }
                $temp_coupon['coupon_discount'] = $cval['discount_percent'];
                if(isset($cval['campaign'][0]['vendor_coupon'])){
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
            $ovtemp_coupon['coupon_code'] = $ovcval['code'];
            $ovtemp_coupon['desc'] = $ovcval['description'];
            if(isset($ovcval['long_description']) && !empty($ovcval['long_description'])){
                $ovtemp_coupon['long_desc']= $ovcval['long_description'];
            }
            $this->appliedCouponData[] =  $ovtemp_coupon;
            unset($ovtemp_coupon);
        }

        foreach($noCouponOffersData as $nco_val){
            $this->appliedCouponData[] = array(
               'coupon_code' => $nco_val['code'],
                'desc' => $nco_val['description'],
                'long_desc' => $nco_val['long_desc'],
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
                        $response=  $this->customerreward->verifyCouponFinderDetail($ratecard, $cval['code'],null, null, null, null, null, null, null, null, $corporate_discount_coupon = true,$vendor_page_without_login, $cval, $finder, $srval);
                        if(!isset($response['error_message'])) {
                          $isCouponAppliedFlag = true;
                          $services_coupon[$ratecard['_id']]['coupons'][$cval['_id']] =  $response['data']['final_amount'];
                        }    
                    }    
            }
            if($isCouponAppliedFlag){
            $temp_coupon["_id"]= $cval['_id'];
            $temp_coupon['code']= $cval['code'];
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
            if(isset($cval['campaign'][0]['hero_coupon']) && $cval['campaign'][0]['hero_coupon']==1 ){
                $temp_coupon['default_selected'] = $cval['campaign'][0]['hero_coupon'];
            }
            $offers['offers']['options'][] = $temp_coupon;
            unset($temp_coupon);
         }
        }
        
        foreach($other_vendor_coupons_data as $ovcval) {

            $ovtemp_coupon['code']= $ovcval['code'];
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
            $ncotemp_coupon['code']= $nco_val['code'];
            $ncotemp_coupon['description']= $nco_val['description'];
            if(isset($nco_val['long_desc']) && !empty($nco_val['long_desc'])){
                if($this->checkAndroidVersion(['android'=>5.33])){
                    foreach($nco_val['long_desc'] as $ldkey => $val){
                        $ncotemp_coupon['terms'][$ldkey]= "$val <br>";
                    }
                }else {
                $ncotemp_coupon['terms']= $nco_val['long_desc'];
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
        $projection = ['_id' => 0, 'code' => ['$toUpper' => '$code'], 'description' => 1, 'long_description' => 1, 'is_applicable' => ['$literal' => true]];
        if($type=='pass') {
            $matchClause['$or'] = [
                ['campaign.vendor_coupon' => ['$eq' => '1']],
                ['campaign.pps_coupon' => ['$eq' => '1']]
            ];
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
                    ['$match' => $vendorHeroCoupon],
                    ['$project' => $projection]
                ]
            ];
            $facet['other_coupons'] = [['$match' => $nonVendorHeroCoupon], ['$project' => $projection]];
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
		$finalArray = $coupons[0]['hero_coupons'];
		$finalArray = array_merge($finalArray, $coupons[0]['other_coupons']);
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
            // if($order_id) {
            //     $data =  Order::select("type","pass._id","pass.pass_type")->where("_id",(int)$order_id)->first();
            //     $coupon_condition  =  array("ratecard_type"=>$order_data->type,"pass_type" => $order_data->pass['pass_type'], "status" => "1","campaign.campaign_id" => "$campaign_id");
            // } else if($pass_id) {
            //     $data = Pass::select("pass_type")->where("pass_id",(int)$pass_id)->first();    
            //     $coupon_condition  =  array("ratecard_type"=>$type,"pass_type" => $pass_data->pass_type, "status" => "1","campaign.campaign_id" => "$campaign_id");
            // }
            // $coupon_data = $couponObj->getActiveVendorCoupon($coupon_condition);
        }
        else if(!empty($ratecard_id)){
            $data = Ratecard::select("type")->where('_id',(int)$ratecard_id)->first();
        }
        $coupon_data = $this->getActiveCouponsByType($campaign_id, [$data['type']]);
        
        // $noCouponOffersData = $nocouponcodeofferobj->getActiveVendorNoCouponOffer($campaign_id);
        $noCouponOffersData = $this->getActiveNoCouponOffersByType($campaign_id, $data['type']);
        // $temp_coupon = $coupon_data;
        $finalArray = array_merge($coupon_data, $noCouponOffersData);
        // foreach($noCouponOffersData as $nco_val){
        //     $temp_coupon[] = array(
        //         'code' => strtoupper($nco_val['code']),
        //         'description' => $nco_val['description'],
        //     ); 
        // }
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
