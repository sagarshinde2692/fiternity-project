<?PHP

use App\Services\RazorpayService as RazorpayService;
use App\Services\PassService as PassService;

class RazorpayController extends \BaseController {
    protected $razorpayService;

	public function __construct(RazorpayService $razorpayService, PassService $passService) {
		parent::__construct();
        $this->razorpayService = $razorpayService;
        $this->passService = $passService;
    }
    
    public function createSubscription () {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        $response = ['status' => 400, 'data' => null, 'msg' => 'Failed'];
        if(!empty($data['order_id'])) {
            $_response = $this->razorpayService->createSubscription($data['order_id']);
            if(!empty($_response)) {
                $response = ['status' => 200, 'data' => $_response, 'msg' => 'Success'];
            }
        }
        return $response;
    }

    public function storePaymentDetails() {
        $data = Input::json()->all();
        $response = ['status' => 400, 'data' => null, 'msg' => 'Failed'];
        if(!empty($data['payment_id']) && $data['order_id']) {
            $response = $this->razorpayService->storePaymentDetails($data['order_id'], $data['payment_id']);
        }
        if(!empty($response)) {
            $response = ['status' => 200, 'data' => $response, 'msg' => 'Success'];
        }
        return $response;
    }

    public function razorpayWebhooks(){
        $data = Input::json()->all();
        $key = Config::get('app.webhook_secret_key');
        $signature = Request::header('X-Razorpay-Signature');
        $body = Request::getContent();
        $expected_signature = hash_hmac('sha256', $body, $key);
        Log::info("webhooks data:::::::::::::::::::::::::::::::::::::::::::::::::::::::", [$body, $expected_signature, $signature]);
        switch($data['event']){
            case "subscription.charged": $this->charged($data, $body, $signature, $key, $expected_signature);break;
            case "subscription.pending": $this->pending($data);break;
            case "subscription.halted": $this->halted($data);break;
            case "subscription.cancelled": $this->cancelled($data);break;
            case "subscription.activated": $this->activated($data);break;
            case "subscription.completed": $this->completed($data);break;
            default : $this->webhookStore($data);break;
        }
    }

    public function activated($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }

    public function charged($data, $body, $signature, $key, $expected_signature){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
        
        $subs_id = $data['payload']['subscription']['entity']['id'];
        $plan_id = $data['payload']['subscription']['entity']['plan_id'];
        $amount = ((float)$data['payload']['payment']['entity']['amount'])/100;
        $payment_id = $data['payload']['payment']['entity']['id'];
        $order_id = $data['payload']['payment']['entity']['order_id'];

        $order = Order::active()
        ->where('rp_subscription_id', $subs_id)
        ->where('rp_plan_id', $plan_id)
        ->orderby('_id')
        ->first();
        
        $input = array(
            "customer_email"=> $order['customer_email'], 
            "customer_name"=> $order['customer_name'], 
            "customer_phone"=> $order['customer_phone'],
            "pass_id"=> $order['pass']['pass_id'],
            "rp_subscription_id" => $subs_id,
            "plan_id" =>  $plan_id,
            "amount" => $amount,
            "payment_id" =>$payment_id,
            "orignal_pass_order_id" =>$order['_id']
        );

        $pass_capture = $this->passService->passCapture($input);

        if(isset($pass_capture['status']) && $pass_capture['status']==200){
            $success_data = [
                "order_id" => $pass_capture["data"]["_id"],
                "type" => "webhook",
                "razorpay"=>[
                    "razorpay_payment_id" => $input['payment_id'],
                    "rp_subscription_id" => $input['rp_subscription_id'],
                    "rp_body" => $body,
                    "key" => $key,
                    "razorpay_signature" => $signature
                ],
            ];
            $this->passService->passSuccess($success_data);
        }
    }

    public function completed($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }

    public function pending($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }

    public function halted($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }

    public function cancelled($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();

        $subs_id = $data['payload']['subscription']['entity']['id'];

        // $order = Order::active()
        // ->where('rp_subscription_id', $subs_id)
        // ->orderby('_id')
        // ->get();
        // //cancel order
        // Log::info('order data::::::::::::::::::', [$order]);
    }

    public function webhookStore($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }
}