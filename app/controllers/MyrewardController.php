<?php

use App\Services\Utilities as Utilities;

class MyrewardController extends BaseController {

    protected $utilities;

    public function __construct(
        Utilities $utilities
    )
    {
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

        if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android") && isset($_GET['app_version']) && ((float)$_GET['app_version'] < 2.6)){
            $query->where("payload.booktrial_type","vip_booktrials");
        }

        if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios")){
            $query->where("payload.booktrial_type","vip_booktrials");
        }

        $myrewards = array();
        $myrewards = $query->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();

        if(count($myrewards) > 0){
            $myrewards->toArray();
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