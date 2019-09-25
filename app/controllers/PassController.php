<?PHP
use App\Services\PassService as PassService;
use App\Services\Utilities;

class PassController extends \BaseController {

    public function __construct(PassService $passService, Utilities $utilities) {
        parent::__construct();
        $this->passService = $passService;
        $this->utilities = $utilities;
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
        $passes = $this->passService->listPasses($customer_id, $pass_type, $device, $version);
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

    public function passCaptureAuto($job ,$input){
        if($job){
            $job->delete();
        }
        
        Log::info('auto pass purchase input::::', [$input]);
        $input = 
        $order = $input['order'];

        $data = [
            "amount"=> 0,
            "booking_for_others"=> false,
            "cashback"=> false,
            "customer_email"=> $order['customer_email'],
            "customer_name"=> $order['customer_name'],
            "customer_phone"=> $order['customer_phone'],
            "customer_source"=> $order['customer_source'],
            "wallet"=> false,
            "device_type"=> $order['device_type'],
            "env"=> 1,
            "finder_id"=> 0,
            "gcm_reg_id"=> $order['gcm_reg_id'],
            "gender"=> $order['gender'],
            "pass_id"=> $order['ratecard_flags']['complementary_pass_id'],
            "preferred_starting_date"=> $order['preferred_starting_date'],
            "pt_applied"=> false,
            "customer_quantity"=> 1,
            "reward_ids"=> [],
            "type"=> "pass",
            "membership_order_id" => $order['_id'],
            "complementary_pass" => true
        ];

        $captureResponse = $this->passCapture($data);

        Log::info('inside schudling complementary pass purchase capture response:', [$captureResponse]);
        if(!empty($captureResponse) && empty($captureResponse['status']) || empty($captureResponse['data']) || $captureResponse['status']!= 200){
            $order->complementary_pass_purchase_response = [
                'at_state' => 'caputre', 
                'data' => $captureResponse
            ];
        }
        else {
            $complementary_pass_success_response = $this->passService->passSuccessPayU($captureResponse['data']);
            Log::info('inside schudling complementary pass purchase success response:', [$complementary_pass_success_response]);
            $order->complementary_pass_purchase_response =  [
                'at_state' => 'caputre', 
                'data' => $complementary_pass_success_response
            ];
        }
    }
}