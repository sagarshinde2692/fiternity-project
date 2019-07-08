<?PHP
use App\Services\PassService as PassService;
class PassController extends \BaseController {

    public function __construct(PassService $passService) {
        parent::__construct();
        $this->passService = $passService;
    }

    public function listPasses(){

        $jwt_token = Request::header('Authorization');
        $customer_id = null;
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }
        $passes = $this->passService->listPasses($customer_id);
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

        return $this->passService->passSuccess($data);

    }

    public function passTermsAndCondition(){
        $passTerms = \Config::get('pass.terms');
        return array("status"=> 200, "data"=> $passTerms[0], "msg"=> "success");
    }

    public function passFrequentAskedQuestion(){
        $passFaq = \Config::get('pass.question_list');
        return array("status"=> 200, "data"=> $passFaq, "msg"=> "success");
    }


    public function orderPassHistory($customer_email, $offset = 0, $limit = 10) {
        return $this->passService->orderPassHistory($customer_email, $offset, $limit);
    }

}