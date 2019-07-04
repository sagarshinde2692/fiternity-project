<?PHP

use App\Services\RazorpayService as RazorpayService;

class RazorpayController extends \BaseController {
    protected $razorpayService;

	public function __construct(RazorpayService $razorpayService) {
		parent::__construct();
        $this->razorpayService = $razorpayService;
    }
    
    public function createSubscription () {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        $response = ['status' => 400, 'data' => null, 'msg' => 'Failed'];
        if(!empty($data['pass_id'])) {
            $response = $this->razorpayService->createSubscription($data['pass_id']);
        }
        if(!empty($response)) {
            $response = ['status' => 200, 'data' => $response, 'msg' => 'Success'];
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
        //Log::info("webhooks data:::::::::::::::::::::::::::::::::::::::::::::::::::::::", [$data]);
        switch($data['event']){
            case "subscription.activated": $this->activated($data);break;
            case "subscription.charged": $this->charged($data);break;
            case "subscription.completed": $this->completed($data);break;
            case "subscription.pending": ;
            case "subscription.halted": ;
            case "subscription.cancelled": ;
            case "payment.authorized": $this->authorized($data);break;
        }
    }

    public function activated($data){
        $subs_id = $data['payload']['subscription']['entity']['id'];

        $order = Order::find('rp_payment_id', $subs_id);
        $order->rp_actived = true;

        $razorpaySubs = RazorpaySubscription::find('rp_subscription_id', $subs_id);
        $razorpaySubs->rp_activate = true;

        $webhook = new RazorpayWebhook($data);

        $order->update();
        $razorpaySubs->updated();
        $webhook->save();
    }

    public function charged($data){

        $subs_id = $data['payload']['subscription']['entity']['id'];
        $plan_id = $data['payload']['subscription']['entity']['plan_id'];
        $amount = $data['payload']['payment']['entity']['amount'];
        $payment_id = $data['payload']['payment']['entity']['id'];
        $order_id = $data['payload']['payment']['entity']['order_id'];
        $status = $data['payload']['payment']['entity']['status'];
        $start_at = $data['payload']['subscription']['entity']['current_start'];
        $end_at = $data['payload']['subscription']['entity']['current_end'];

        $order = Order::where('rp_subscription_id', $subs_id)
        ->where('rp_status', 'authorized')
        ->where('rp_plan_id', $plan_id)
        ->orderby('_id')
        ->first();

        $id = Order::maxId()+1;
        $data1['_id'] = $id;
        $newOrder = new Order($data1);

        // $new_id = Order::maxId()+1;
        // $data1['_id'] = $new_id;
        // $data = new Order($data1);
        $newOrder['_id'] = $data1['_id'];
        $newOrder['order_id'] = $newOrder['_id'];
        $newOrder['orderid'] = $newOrder['_id'];
        $newOrder['orignal_pass_order_id'] = $order['_id'];
        $newOrder['amount'] = $amount;
        $newOrder['rp_payment_id'] = $payment_id;
        $newOrder['rp_status'] = $status;
        $newOrder['rp_order_id'] = $order_id;
        $newOrder['pass'] = $order['pass'];
        $newOrder['pass_type'] = $order['pass_type'];
        $newOrder['customer_id'] = $order['customer_id'];
        $newOrder['customer_email'] = $order['customer_email'];
        $newOrder['customer_phone'] = $order['customer_phone'];
        $newOrder['payment_mode'] = $order['payment_mode'];
        $newOrder['total_credits'] = $order['total_credits'];
        $newOrder['subscription_id'] = $order['subscription_id'];
        $newOrder['plan_id'] = $order['plan_id'];
        $newOrder['rp_subscription_id'] = $order['rp_subscription_id'];
        $newOrder['rp_plan_id'] = $order['rp_plan_id'];
        $newOrder['status'] = "1";
        $newOrder['type'] = $order['type'];
        $newOrder['start_date'] = new MongoDate($start_at);
        $newOrder['end_date'] = new MongoDate($end_at);

        Log::info('prepared data::::::::::::::::', [$newOrder]);
        //$data = new Order($data);
        try{
            $newOrder->save();
        }catch(\Exception $e){
            Log::info('erro in saving ::::::::::', [$e]);
        }
    }

    public function completed($data){
        $subs_id = $data['payload']['subscription']['entity']['id'];

        $order = Order::find('rp_payment_id', $subs_id);
        $order->rp_completed = true;
        $order->update();
    }

    public function authorized($data){
        $subs_id = $data['payload']['payment']['entity']['id'];
        $rp_status = $data['payload']['payment']['entity']['status'];
        Log::info("inside authorized ", [$subs_id]);
        $order = Order::findOrFail(['rp_payment_id', $subs_id]);
        Log::info('orders:::::::::', [($order)]);
        if($order){
            // $order->rp_status = $rp_status;
            // $order->update();
        }

        $webhook->save();
    }

}