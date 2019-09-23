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

    public function passTab(){
    
        $input= Input::all();

        $rules= [
            'city' => 'required',
            'lat' => 'required',
            'lon' => 'required'
        ];

        $validator = Validator::make($input,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' => error_message($validator->errors())), 400);
        }
        $city =  $input['city'];

        $coordinate = [
            'lat' => $input['lat'],
            'lon' => $input['lon']
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
			$passOrder = Order::where('status', '1')->where('type', 'pass')->where('customer_id', '=', $customer_id)->where('end_date','>=',new MongoDate())->orderBy('_id', 'desc')->first();
			if(!empty($passOrder)) {
				$passPurchased = true;
			}
		}
		
		if($passPurchased && !empty($passOrder['pass']['pass_type'])) {
			$result['onepass_post'] = $this->passService->passTabPostPassPurchaseData($passOrder['customer_id'], $city, false, $coordinate);
		}else {
            $result['onepass_pre'] = Config::get('pass.before_purchase_tab');
            $pps_near_by = $this->passService->workoutSessionNearMe($city, $coordinate);
			$result['onepass_pre']['near_by']['subheader'] = $pps_near_by['header'];
			$result['onepass_pre']['near_by']['data'] = $pps_near_by['data'];
		}

		$response = Response::make($result);
		return $response;
	}
}