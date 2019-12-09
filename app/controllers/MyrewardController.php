<?php

use App\Services\Utilities as Utilities;

class MyrewardController extends BaseController {

    protected $utilities;

    public function __construct(
        Utilities $utilities
    )
    {
        parent::__construct();
        $this->utilities = $utilities;
    }


    public function createMyReward(){

        try {
            
            $data = Input::json()->all();
            $data = $this->utilities->removeEmptyKeys($data);
            Log::info('Add reward to customer myrewards',$data);

            
            return Response::json(array("status" => 200,"message" => "success"),200);

        } catch (Exception $e) {

             return Response::json(array('status' => 404,'message' => $e->getMessage()),404);
        }                           
    }

    public function listMyRewards($offset = 0, $limit = 10){
    
        $jwt_token = Request::header('Authorization');
        $decoded = $this->utilities->customerTokenDecode($jwt_token);
        $customer_id = (int)$decoded->customer->_id;

        $query      =   Myreward::where('customer_id',$customer_id);

        $query->where("payload.booktrial_type","vip_booktrials");

        $myrewards = array();
        $myrewards = $query->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();

        if(count($myrewards) > 0){

            $myrewards = $myrewards->toArray();

            foreach ($myrewards as $key => $value){


                $created_at = date('Y-m-d h:i:s',strtotime($value['created_at']));
                $validity_in_days = $value['created_at'];

                $validity_date_unix = strtotime($created_at . ' +'.(int)$value['validity_in_days'].' days');
                $current_date_unix = time();


                if($validity_date_unix < $current_date_unix){
                    $validity_in_days = 0;
                }else{

                    $validity_in_days = ceil(($validity_date_unix - $current_date_unix)/(60*60*24));

                }

                $myrewards[$key]['validity_in_days'] = $validity_in_days;

                if(isset($value['payload']) && isset($value['payload']['amount']) && $value['payload']['amount'] != "" && isset($value['quantity']) && $value['quantity'] != ""){
                    $myrewards[$key]['payload']['amount'] = $value['payload']['amount'] * $value['quantity'];
                }

            }
        }

        return Response::json(array('status' => 200,'data' => $myrewards), 200);
    }

    public function listMyRewardsV1($offset = 0, $limit = 10){
    
        $jwt_token = Request::header('Authorization');
        $decoded = $this->utilities->customerTokenDecode($jwt_token);
        $customer_id = (int)$decoded->customer->_id;

        $query      =   Myreward::where('customer_id',$customer_id);

        $myrewards = array();
        $myrewards = $query->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();

        if(count($myrewards) > 0){

            $myrewards = $myrewards->toArray();

            $required_info = Config::get('loyalty_screens.voucher_required_info');
            unset($required_info['address']);
            foreach ($myrewards as $key => &$value){

                if(in_array($value['reward_type'],['swimming_sessions','sessions']) && !empty($this->app_version) && floatval($this->app_version) < 4.9){
                    unset($myrewards[$key]);
                    continue;
                }

                $value["cta"] = "Get Now";

                if($value['status'] == "1"){
                    $value["cta"] = "Claimed";
                }

                if($value['reward_type']=='mixed'){
                    $value['claimed'] = $value['quantity'];
                }

                $created_at = date('Y-m-d H:i:s',strtotime($value['created_at']));
                
                $validity_date_unix = strtotime($created_at . ' +'.(int)$value['validity_in_days'].' days');
                $current_date_unix = time();

                $validity_in_days = 0;

                if($validity_date_unix > $current_date_unix){
                    $validity_in_days = ceil(($validity_date_unix - $current_date_unix)/(60*60*24));
                }

                $myrewards[$key]['validity_in_days'] = $validity_in_days;

                $myrewards[$key]['validity_date'] = "Valid Till : ".date('jS M\, Y g\:i A',$validity_date_unix);
                
                if(isset($value['payload']) && isset($value['payload']['amount']) && $value['payload']['amount'] != "" && isset($value['quantity']) && $value['quantity'] != ""){
                    $myrewards[$key]['payload']['amount'] = $value['payload']['amount'] * $value['quantity'];
                }

                if(in_array($value['reward_type'],['sessions','swimming_sessions'])){
                    $value["cta"] = "Unlock Coupon";
                }

                if(!empty($value['coupon_detail'])){

                    $value["claimed"] = 0;

                    if(!empty($this->device_type)){

                        $value["cta"] = "Schedule Now";
                        $value["status"] = "0";
                    }

                    foreach ($value['coupon_detail'] as &$val) {

                        $val['text'] = "Your code is ".$val['code']." (".$val['amount'].")";
                        $val['usage_text'] = $val['claimed']."/".$val['quantity']." booked";

                        $value["claimed"] += $val['claimed'];
                    }

                    if($value["quantity"] == $value["claimed"]){
                        $value["status"] = "1";
                        $value["cta"] = "Claimed";
                    }

                    $value["url"] = "ftrnty://ftrnty.com/pps";

                    if($value['reward_type'] == 'swimming_sessions'){
                        $value["url"] = "ftrnty://ftrnty.com/pps?cat=swimming-pools";
                    }

                    $value["copy_text"] = "Copied";
                }

                if($value['reward_type'] == 'mixed'){
                    $value["status"] = "1";
                    $value["cta"] = "Claimed";
                }

                if(!empty($value['tshirt_include'])){
                    $value['required_info'] = $required_info;
                }
            }
        }

        return Response::json(array('status' => 200,'data' => $myrewards), 200);
    }

    public function redeemMyReward($myreward_id){

        $jwt_token = Request::header('Authorization');
        $decoded = $this->utilities->customerTokenDecode($jwt_token);
        $customer_id = $decoded->customer->_id;

        $reward = Myreward::find($myreward_id);

        if(empty($reward)){
            $resp   =   array('status' => 400,'message' => "Invalid MyReward ID");
            return  Response::json($resp, 400);
        }

        switch ($reward['type']){
            case 'fitternity_voucher':
                break;
            case 'fitness_kit':
                break;
            case 'sessions':
                break;
            default:
                break;
        }
    }
    
}