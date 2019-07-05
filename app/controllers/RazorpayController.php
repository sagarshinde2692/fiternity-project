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

    public function razorpayWebhooks(){
        $data = Input::json()->all();
        $key = Config::get('app.webhook_secret_key');
        $signature = Request::header('X-Razorpay-Signature');
        $body = Request::getContent();
        Log::info("webhooks data:::::::::::::::::::::::::::::::::::::::::::::::::::::::", [$signature]);
        switch($data['event']){
            case "subscription.charged":return $this->charged($data, $body, $signature, $key);break;
            case "subscription.pending":return $this->pending($data);break;
            case "subscription.halted":return $this->halted($data);break;
            case "subscription.cancelled":return $this->cancelled($data);break;
            case "subscription.activated":return $this->activated($data);break;
            case "subscription.completed":return $this->completed($data);break;
            default : $this->webhookStore($data);break;
        }
    }

    public function activated($data){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
    }

    public function charged($data, $body, $signature, $key){
        $webhook = new RazorpayWebhook($data);
        $webhook->save();
        
        $subs_id = $data['payload']['subscription']['entity']['id'];
        $plan_id = $data['payload']['subscription']['entity']['plan_id'];
        $amount = ((float)$data['payload']['payment']['entity']['amount'])/100;
        $payment_id = $data['payload']['payment']['entity']['id'];

        $prev_order = Order::where('payment_id', $payment_id)->first();

        if(!empty($prev_order)){
            return ['status'=>400, 'message'=>'Repeat request'];
        }

        $order = Order::active()
        ->where('rp_subscription_id', $subs_id)
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
            "rp_orignal_pass_order_id" =>$order['_id'],
            "webhook_id" => $webhook->id
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
            $success = $this->passService->passSuccess($success_data);
            if(!empty($success['status']) && $success['status'] == 200){
                return ['status'=>200, 'message'=>"Order created successfully"];
            }
        }

        return ['status'=>400, 'message'=>"Something went wrong"];
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