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
                $temp_coupon['long_desc'] = $cval['description'];
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
            $this->appliedCouponData[] = array(
                'coupon_code' => $ovcval['code'],
                 'desc' => $ovcval['description'],
                 'long_desc' => $ovcval['description'],
             );
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
                "text" => "View Offers",
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
            $temp_coupon['terms']= array($cval['description']);
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
            $offers['offers']['options'][] = array(
                'code' => $ovcval['code'],
                 'description' => $ovcval['description'],
                 'terms' => array($ovcval['description']),
             );
        }
        
        foreach($noCouponOffersData as $nco_val){
            $offers['offers']['options'][] = array(
               'code' => $nco_val['code'],
                'description' => $nco_val['description'],
                'terms' => array($nco_val['long_desc']),
            ); 
        }
        return array("services_coupon" => $services_coupon,"offers"=> $offers['offers']);
    }

    public function getlistvalidcoupons($type = null,$order_id=null,$pass_id=null){
        // $campaign_data = $this->utilities->getCampaignData();
        // if(empty($campaign_data)){
        //     return false;
        // }
        $couponObj =  new Coupon();
        $nocouponcodeofferobj =  new Nocouponcodeoffers();
        $campaign_id = Config::get('app.config_campaign_id');//$campaign_data->_id;
        if($type=="pass" && $order_id){
            $order_data =  Order::select("type","pass._id","pass.pass_type")->where("_id",(int)$order_id)->first();
            $coupon_condition  =  array("ratecard_type"=>$order_data->type,"pass_type" => $order_data->pass['pass_type'],
            "status" => "1","campaign.campaign_id" => "$campaign_id");
        } else if($type=="pass" && $pass_id){
            $pass_data = Pass::select("pass_type")->where("pass_id",(int)$pass_id)->first();
            $coupon_condition  =  array("ratecard_type"=>$type,"pass_type" => $pass_data->pass_type,
            "status" => "1","campaign.campaign_id" => "$campaign_id");
        } else{
        $coupon_condition  =  array("campaign.vendor_coupon"=>"1","status" => "1","campaign.campaign_id" => "$campaign_id");
        }
        $coupon_data = $couponObj->getActiveVendorCoupon($coupon_condition);
        $noCouponOffersData = $nocouponcodeofferobj->getActiveVendorNoCouponOffer($campaign_id);
        $temp_coupon = array();
        foreach($coupon_data as $val){
            $temp_coupon[]= array('code' => strtoupper($val['code']),'description' => $val['description'],"isApplicable"=> true);
        }
        
        foreach($noCouponOffersData as $nco_val){
            $temp_coupon[] = array(
               'code' => strtoupper($nco_val['code']),
                'description' => $nco_val['description'],
            ); 
        }
        return $temp_coupon;
    }
}

?>
