<?PHP
use App\Services\PassService as PassService;
use App\Services\Utilities;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;

class PassController extends \BaseController {

    public function __construct(PassService $passService, Utilities $utilities, CustomerMailer $customerMailer, CustomerSms $customerSms) {
        parent::__construct();
        $this->passService = $passService;
        $this->utilities = $utilities;
        $this->customerMailer = $customerMailer;
        $this->customerSms = $customerSms;
    }

    public function listPasses($pass_type=null){

        $jwt_token = Request::header('Authorization');
        $device = Request::header('Device-Type');
        $version = Request::header('App-Version');
        $customer_id = null;
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $input = Input::all();
        $category = null;
        $city = null;

        if(!empty($input['category'])){
            $category = $input['category'];
        }

        if(!empty($input['city'])){
            $city = strtolower($input['city']);
        }
        
        $passes = $this->passService->listPasses($customer_id, $pass_type, $device, $version, $category, $city);
        if(empty($passes)) {
            return [
                "status" => 400,
                "data" => null,
                "msg" => "failed"
            ];
        }
        $response = [
            "status" => 200,
            "data" => $passes,
            "msg" => "success"
        ];

        return $response;
    }

    public function passCapture(){

        $data = Input::json()->all();

        $rules = [
            'pass_id'=>'required | integer',
            'customer_name'=>'required',
            'customer_email'=>'required',
            'customer_phone'=>'required',
        ];

        
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }
        
        return $order_creation_response = $this->passService->passCapture($data);

        if(empty($order_creation_response['status']) || $order_creation_response['status'] != 200){
            return ['status'=>400, 'message'=>'Error while creating order.'];
        }

    }

    public function passSuccess(){

        $data = Input::json()->all();

        $rules = [
            'order_id'=>'required | integer',
        ];
        
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }

        if(!empty($data['razorpay'])){
            
            $rules = [
                'order_id'=>'required | integer',
                'razorpay.razorpay_subscription_id'=>'required | string',
                'razorpay.razorpay_signature'=>'required | string',
                'razorpay.razorpay_payment_id'=>'required | string'
            ];
            
            $validator = Validator::make($data,$rules);
    
            if ($validator->fails()) {
                return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
            }
            
            $data['razorpay']['key'] = Config::get('app.razorPaySecret');
            $data['razorpay']['rp_body'] = $data['razorpay']['razorpay_payment_id'].'|'.$data['razorpay']['razorpay_subscription_id'];
            $data['payment_id'] = $data['razorpay']['razorpay_payment_id'];
        
        }

        $response =  $this->passService->passSuccess($data);
        if(!empty($response['order'])){
            $response['data']['branch_obj'] = $this->utilities->branchIOData($response['order']);
            unset($response['order']);
        }
        return $response;

    }

    public function passTermsAndCondition(){
        return $this->passService->passTermsAndCondition();
    }

    public function passFrequentAskedQuestion(){
        $passFaq = \Config::get('pass.question_list');
        return array("status"=> 200, "data"=> $passFaq, "msg"=> "success");
    }


    public function orderPassHistory() {
        $jwt_token = Request::header('Authorization');
        $customer_id = null;
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }
        return $this->passService->orderPassHistory($customer_id);
    }

    public function homePostPassPurchaseData() {
        $jwt_token = Request::header('Authorization');
        $customer_id = null;
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }
        return [ 'status' => 200, 'data' => $this->passService->homePostPassPurchaseData($customer_id), 'message' => 'Success' ];
    }

    public function passTab(){
    
        $input= Input::all();

        // $rules= [
        //     'city' => 'required',
        // ];

        // $validator = Validator::make($input,$rules);

        // if ($validator->fails()) {
        //     return Response::json(array('status' => 400,'message' => error_message($validator->errors())), 400);
        // }
        $city =  !empty($input['city'])? $input['city'] : 'mumbai' ;

        $coordinate = [
            'lat' => !empty($input['lat']) ? $input['lat']: "",
            'lon' => !empty($input['lon']) ? $input['lon'] : ""
        ];
        
		$decoded = null;
        $jwt_token = Request::header('Authorization');
		if(!empty($jwt_token)){
            $decoded = customerTokenDecode($jwt_token);
            if(!empty($decoded)){
                $customeremail = $decoded->customer->email;
                $customer_id = $decoded->customer->_id;
            }
		}
		
		$passPurchased = false;
		$passOrder = null;

		if(!empty($customeremail)) {
            $customer = Customer::where('_id', $customer_id)->first();
			$passOrder = Order::where('status', '1')->where('type', 'pass')->where('customer_id', '=', $customer_id)->where('end_date','>=',new MongoDate())->orderBy('_id', 'desc')->first();
			if(!empty($passOrder)) {
				$passPurchased = true;
			}
		}
		
		if(!$passPurchased && !empty($passOrder['pass']['pass_type'])) {
			$result['onepass_post'] = $this->passService->passTabPostPassPurchaseData($passOrder['customer_id'], $city, false, $coordinate, $customer);
		}else {
            $coordinate['city'] = $city;
            $result['onepass_pre'] = Config::get('pass.before_purchase_tab');
            //$pps_near_by = $this->passService->workoutSessionNearMe($city, $coordinate);
            $vendor_near_by = $this->utilities->getVendorNearMe($coordinate);
            Log::info('near by vendors');
            $result['onepass_pre']['near_by']['subheader'] = $vendor_near_by['header'];
            $result['onepass_pre']['near_by']['near_by_vendor'] = $vendor_near_by['data'];
		}

		$response = Response::make($result);
		return $response;
    }
    
    public function passCaptureAuto($job ,$input){
        if($job){
            $job->delete();
        }
        
        $order = $input['order'];
        $forced = !empty($input['forced']) ? $input['forced'] : false;

        $data = [
            "amount"=> 0,
            "booking_for_others"=> false,
            "cashback"=> false,
            "customer_email"=> $order['customer_email'],
            "customer_name"=> $order['customer_name'],
            "customer_phone"=> $order['customer_phone'],
            "customer_source"=> $order['customer_source'],
            "customer_id" => $order['customer_id'],
            "wallet"=> false,
            "device_type"=> $order['device_type'],
            "env"=> 1,
            "finder_id"=> 0,
            "gcm_reg_id"=> $order['gcm_reg_id'],
            "gender"=> $order['gender'],
            "pass_id"=> $order['combo_pass_id'],
            "preferred_starting_date"=> $order['preferred_starting_date'],
            "pt_applied"=> false,
            "customer_quantity"=> 1,
            "reward_ids"=> [],
            "type"=> "pass",
            "membership_order_id" => $order['_id']
        ];

        if(!empty($order['ratecard_flags']['onepass_attachment_type'])){
            $data["onepass_attachment_type"] = $order['ratecard_flags']['onepass_attachment_type'];
        }

        $captureResponse = $this->passService->passCapture($data);

        $resp = $captureResponse;
        Log::info('inside schudling complementary pass purchase capture response:', [$captureResponse]);
        if(!empty($captureResponse) && empty($captureResponse['status']) || empty($captureResponse['data']) || $captureResponse['status']!= 200){
            $order_update = Order::find($data['orderid']);
            $order_update->complementary_pass_purchase_response = [
                'at_state' => 'caputre', 
                'data' => $captureResponse
            ];
            $order_update->update();
        }
        else {
            $captureResponse['data']['internal_success'] = true;
            $captureResponse['data']['verify_hash'] = 'internal_success';
            $captureResponse['data']['order_id'] = $captureResponse['data']['orderid'];
            $complementary_pass_success_response = $this->passService->passSuccessPayU($captureResponse['data']);
            Log::info('inside schudling complementary pass purchase success response:', [$complementary_pass_success_response]);
            $resp = $complementary_pass_success_response;
        }

        if(!empty($forced)){
            return $resp;
        }
    }

    public function localPassRatecards(){
        $data = Input::all();

        $rules= [
            'city'=>'required'
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }
        $type = 'red';
        if(!empty($data['type'])){
            $type= $data['type'];
        }

        $ratecards = $this->passService->localPassRatecards($type, $data['city']);

        $resp = [ 'status' => 200, 'data' => $ratecards, 'message' => 'Success'];

        if(empty(count($ratecards))){
            $resp['message'] = "wo dont serve in ".$data['city']." as of now. ".$type. " pass";
            $resp['status'] = 400;
        }

        return $resp;
    }
    
    public function passCaptureAutoForce(){
        $input = Input::all();

        $rules = [
            'order_id'=>'required | integer',
        ];
        
        $validator = Validator::make($input,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }

        $order_data = Order::active()->where('_id', $input['order_id'])->first();

        if(empty($order_data) || empty($order_data['combo_pass_id'])){

            $msg = "Order is not Placed.";
            // if((!empty($order_data['ratecard_flags']['onepass_attachment_type']) && $order_data['ratecard_flags']['onepass_attachment_type'] =='upgrade')){
            //     $msg = "cannot place pass order for this order.";
            // }
            if(empty($order_data['combo_pass_id'])){
                $msg = "Pass is not listed for this order.";
            }

            return [
                'status' => 400,
                'msg' => $msg
            ];
        }

        $pass_order = Order::active()->where("type", 'pass')->where('membership_order_id', $order_data['_id'])->get(['_id', 'type', 'pass']);

        if(!empty($pass_order) && count($pass_order)){
            return [
                'status' => 200,
                'msg' => "Order already placed.",
                'data' => $pass_order
            ];
        }

        $data = [];
        $data['order'] = $order_data;
        $data['forced'] = true;

        $passPurchaseResponse =  $this->passCaptureAuto(null, $data);

        if(!empty($passPurchaseResponse['status']) && $passPurchaseResponse['status']= 200){
            $customer_email = $this->customerMailer->sendPgOrderMail($order_data->toArray());
            $customer_sms = $this->customerSms->sendPgOrderSms($order_data->toArray());

            $order_update = Order::find($order_data['_id']);
            $order_update->forcepass_communication = [
                'sms' => 0, $customer_sms, 
                'email' => $customer_email
            ];

            $order_update->update();
        }
        return $passPurchaseResponse;
    }
}