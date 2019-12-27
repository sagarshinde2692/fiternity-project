<?PHP
use App\Services\PlusService as PlusService;
use App\Services\Utilities;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;

class PlusController extends \BaseController {

    public function __construct(PlusService $plusService, Utilities $utilities, CustomerMailer $customerMailer, CustomerSms $customerSms) {
        parent::__construct();
        $this->plusService = $plusService;
        $this->utilities = $utilities;
        $this->customerMailer = $customerMailer;
        $this->customerSms = $customerSms;
    }

    public function plusCapture(){

        $data = Input::json()->all();

        $rules = [
            'plus_id'=>'required | integer',
            'customer_name'=>'required',
            'customer_email'=>'required',
            'customer_phone'=>'required',
        ];

        $data = $this->utilities->preProcessCityData($data);

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }
        
        return $response = $this->plusService->plusCapture($data);
    }

    public function plusSuccess(){

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
            //unset($response['order']);
        }

        if(!empty($response['order']) && (($this->device_type == 'ios' && $this->app_version >= '5.2.4') ||($this->device_type == 'android' && $this->app_version >= '5.31') )){

            $token = createCustomerToken($response['order']['customer_id']);
            unset($response['order']);
            $response['token'] = $token;
            $response = Response::make($response);
            $response->headers->set('token', $token );
            return $response;
        }

        if(!empty($response['order'])){
            unset($response['order']);
        }

        return $response;

    }
}